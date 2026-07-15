<?php

namespace App\Services;

use App\Models\FacebookAdCreative;
use App\Models\FacebookAutomationSetting;
use App\Models\FacebookCommentLog;
use Illuminate\Support\Collection;

class FacebookCommentAutomationService
{
    public function createPendingCommentForPost(string $postId): ?FacebookCommentLog
    {
        $settings = FacebookAutomationSetting::current();

        if (! $settings->is_enabled) {
            return null;
        }

        if (FacebookCommentLog::query()->where('facebook_post_id', $postId)->exists()) {
            return null;
        }

        if (! $this->withinLimits($settings)) {
            return FacebookCommentLog::query()->create([
                'facebook_post_id' => $postId,
                'status' => 'skipped',
                'message' => '',
                'error_message' => 'Saatlik veya günlük yorum limiti doldu.',
            ]);
        }

        $creative = $this->chooseCreative($settings);

        if (! $creative) {
            return FacebookCommentLog::query()->create([
                'facebook_post_id' => $postId,
                'status' => 'skipped',
                'message' => '',
                'error_message' => 'Aktif reklam bulunamadı veya reklam bekleme süresinde.',
            ]);
        }

        $message = trim($creative->comment_text."\n\n".$creative->target_url);
        $scheduledAt = now()->addMinutes(random_int($settings->min_delay_minutes, $settings->max_delay_minutes));

        $log = FacebookCommentLog::query()->create([
            'facebook_ad_creative_id' => $creative->id,
            'facebook_post_id' => $postId,
            'status' => $settings->approval_required ? 'pending' : 'scheduled',
            'message' => $message,
            'scheduled_at' => $scheduledAt,
        ]);

        $creative->forceFill(['last_used_at' => now()])->save();

        return $log;
    }

    private function withinLimits(FacebookAutomationSetting $settings): bool
    {
        $hourCount = FacebookCommentLog::query()
            ->whereIn('status', ['pending', 'scheduled', 'posted'])
            ->where('created_at', '>=', now()->subHour())
            ->count();

        $dayCount = FacebookCommentLog::query()
            ->whereIn('status', ['pending', 'scheduled', 'posted'])
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        return $hourCount < $settings->max_comments_per_hour
            && $dayCount < $settings->max_comments_per_day;
    }

    private function chooseCreative(FacebookAutomationSetting $settings): ?FacebookAdCreative
    {
        /** @var Collection<int, FacebookAdCreative> $creatives */
        $creatives = FacebookAdCreative::query()
            ->where('is_active', true)
            ->where(function ($query) use ($settings): void {
                $query
                    ->whereNull('last_used_at')
                    ->orWhere('last_used_at', '<=', now()->subHours($settings->same_creative_cooldown_hours));
            })
            ->orderByDesc('weight')
            ->get();

        if ($creatives->isEmpty()) {
            return null;
        }

        return $creatives->random();
    }
}
