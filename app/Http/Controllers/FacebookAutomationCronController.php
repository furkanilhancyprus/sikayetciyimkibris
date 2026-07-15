<?php

namespace App\Http\Controllers;

use App\Services\FacebookCommentAutomationService;
use App\Services\FacebookGraphClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FacebookAutomationCronController extends Controller
{
    public function __invoke(
        Request $request,
        FacebookGraphClient $client,
        FacebookCommentAutomationService $automation
    ): JsonResponse {
        $configuredToken = config('facebook_automation.cron_token');

        abort_if(! is_string($configuredToken) || $configuredToken === '', Response::HTTP_NOT_FOUND);
        abort_unless(hash_equals($configuredToken, (string) $request->query('token')), Response::HTTP_FORBIDDEN);

        $created = 0;

        foreach ($client->recentPagePosts(10) as $post) {
            $postId = data_get($post, 'id');

            if (is_string($postId) && $automation->createPendingCommentForPost($postId)) {
                $created++;
            }
        }

        $processed = $automation->postDueScheduledComments(5);

        return response()->json([
            'queued' => $created,
            'processed' => $processed,
            'dry_run' => (bool) config('facebook_automation.dry_run'),
        ]);
    }
}
