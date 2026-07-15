<?php

namespace App\Services;

use App\Models\FacebookAdCreative;
use App\Models\FacebookAutomationSetting;
use App\Models\FacebookCommentLog;
use Illuminate\Support\Collection;
use Throwable;

class FacebookCommentAutomationService
{
    public function __construct(private readonly FacebookGraphClient $client)
    {
    }

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
                'error_message' => 'Saatlik veya gunluk yorum limiti doldu.',
            ]);
        }

        $creative = $this->chooseCreative($settings);

        if (! $creative) {
            return FacebookCommentLog::query()->create([
                'facebook_post_id' => $postId,
                'status' => 'skipped',
                'message' => '',
                'error_message' => 'Aktif reklam bulunamadi veya reklam bekleme suresinde.',
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

    public function approve(FacebookCommentLog $log): FacebookCommentLog
    {
        if ($log->status !== 'pending') {
            return $log;
        }

        $log->forceFill([
            'status' => 'scheduled',
            'error_message' => null,
            'scheduled_at' => $log->scheduled_at ?? now(),
        ])->save();

        return $log;
    }

    public function cancel(FacebookCommentLog $log, string $reason = 'Admin tarafindan iptal edildi.'): FacebookCommentLog
    {
        if (in_array($log->status, ['posted', 'skipped'], true)) {
            return $log;
        }

        $log->forceFill([
            'status' => 'skipped',
            'error_message' => $reason,
        ])->save();

        return $log;
    }

    public function retry(FacebookCommentLog $log): FacebookCommentLog
    {
        if ($log->status !== 'failed') {
            return $log;
        }

        $log->forceFill([
            'status' => 'scheduled',
            'error_message' => null,
            'scheduled_at' => now(),
        ])->save();

        return $log;
    }

    public function postDueScheduledComments(int $limit = 5): int
    {
        $logs = FacebookCommentLog::query()
            ->where('status', 'scheduled')
            ->whereNotNull('message')
            ->where(function ($query): void {
                $query->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now());
            })
            ->oldest('scheduled_at')
            ->limit(max(1, $limit))
            ->get();

        foreach ($logs as $log) {
            $this->postComment($log);
        }

        return $logs->count();
    }

    public function postComment(FacebookCommentLog $log): FacebookCommentLog
    {
        if ($log->status !== 'scheduled') {
            return $log;
        }

        if ((bool) config('facebook_automation.dry_run')) {
            $log->forceFill([
                'status' => 'posted',
                'facebook_comment_id' => 'dry-run-'.$log->id,
                'posted_at' => now(),
                'error_message' => 'DRY_RUN aktif: Facebook API’ye gercek yorum gonderilmedi.',
            ])->save();

            return $log;
        }

        try {
            $commentId = $this->client->createComment($log->facebook_post_id, $log->message);

            $log->forceFill([
                'status' => 'posted',
                'facebook_comment_id' => $commentId,
                'posted_at' => now(),
                'error_message' => null,
            ])->save();
        } catch (Throwable $exception) {
            $log->forceFill([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ])->save();
        }

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
