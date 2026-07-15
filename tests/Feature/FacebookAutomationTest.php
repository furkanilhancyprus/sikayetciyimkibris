<?php

namespace Tests\Feature;

use App\Models\FacebookAdCreative;
use App\Models\FacebookAutomationSetting;
use App\Models\FacebookCommentLog;
use App\Services\FacebookCommentAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacebookAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_facebook_automation_stays_idle_when_disabled(): void
    {
        FacebookAutomationSetting::query()->create([
            'page_name' => 'Haberler KKTC',
            'is_enabled' => false,
            'approval_required' => true,
        ]);

        $log = app(FacebookCommentAutomationService::class)
            ->createPendingCommentForPost('123_456');

        $this->assertNull($log);
        $this->assertDatabaseCount('facebook_comment_logs', 0);
    }

    public function test_facebook_automation_creates_pending_log_for_new_post(): void
    {
        FacebookAutomationSetting::query()->create([
            'page_name' => 'Haberler KKTC',
            'is_enabled' => true,
            'approval_required' => true,
            'min_delay_minutes' => 5,
            'max_delay_minutes' => 10,
            'max_comments_per_hour' => 4,
            'max_comments_per_day' => 25,
            'same_creative_cooldown_hours' => 12,
        ]);

        FacebookAdCreative::query()->create([
            'name' => 'Genel reklam',
            'comment_text' => 'Şikayetçiyim Kıbrıs reklam metni.',
            'target_url' => 'https://sikayetciyimkibris.com',
            'is_active' => true,
            'weight' => 1,
        ]);

        $service = app(FacebookCommentAutomationService::class);
        $log = $service->createPendingCommentForPost('123_456');
        $duplicate = $service->createPendingCommentForPost('123_456');

        $this->assertNotNull($log);
        $this->assertNull($duplicate);
        $this->assertSame('pending', $log->status);
        $this->assertStringContainsString('sikayetciyimkibris.com', $log->message);
        $this->assertDatabaseCount('facebook_comment_logs', 1);
    }
}
