<?php

namespace Tests\Feature;

use App\Models\FacebookAdCreative;
use App\Models\FacebookAutomationSetting;
use App\Models\FacebookCommentLog;
use App\Services\FacebookCommentAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
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
        $this->createEnabledSettings(approvalRequired: true);

        FacebookAdCreative::query()->create([
            'name' => 'Genel reklam',
            'comment_text' => 'Sikayetciyim Kibris reklam metni.',
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

    public function test_scheduled_comment_is_marked_posted_in_dry_run_mode(): void
    {
        Config::set('facebook_automation.dry_run', true);

        $log = FacebookCommentLog::query()->create([
            'facebook_post_id' => '123_456',
            'status' => 'scheduled',
            'message' => 'Test reklam mesaji',
            'scheduled_at' => now()->subMinute(),
        ]);

        app(FacebookCommentAutomationService::class)->postDueScheduledComments();

        $log->refresh();

        $this->assertSame('posted', $log->status);
        $this->assertSame('dry-run-'.$log->id, $log->facebook_comment_id);
        Http::assertNothingSent();
    }

    public function test_scheduled_comment_posts_to_graph_api_when_live(): void
    {
        Config::set('facebook_automation.dry_run', false);
        Config::set('facebook_automation.page_access_token', 'test-token');
        Config::set('facebook_automation.graph_version', 'v25.0');

        Http::fake([
            'graph.facebook.com/v25.0/123_456/comments' => Http::response([
                'id' => 'comment_789',
            ]),
        ]);

        $log = FacebookCommentLog::query()->create([
            'facebook_post_id' => '123_456',
            'status' => 'scheduled',
            'message' => 'Test reklam mesaji',
            'scheduled_at' => now()->subMinute(),
        ]);

        app(FacebookCommentAutomationService::class)->postDueScheduledComments();

        $log->refresh();

        $this->assertSame('posted', $log->status);
        $this->assertSame('comment_789', $log->facebook_comment_id);

        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && $request->url() === 'https://graph.facebook.com/v25.0/123_456/comments'
            && $request['message'] === 'Test reklam mesaji');
    }

    private function createEnabledSettings(bool $approvalRequired): void
    {
        FacebookAutomationSetting::query()->create([
            'page_name' => 'Haberler KKTC',
            'is_enabled' => true,
            'approval_required' => $approvalRequired,
            'min_delay_minutes' => 5,
            'max_delay_minutes' => 10,
            'max_comments_per_hour' => 4,
            'max_comments_per_day' => 25,
            'same_creative_cooldown_hours' => 12,
        ]);
    }
}
