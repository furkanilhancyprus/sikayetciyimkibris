<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FacebookGraphClient
{
    public function recentPagePosts(int $limit = 10): array
    {
        $pageId = $this->pageId();

        $response = $this->request()->get("/{$pageId}/posts", [
            'fields' => 'id,message,created_time,permalink_url',
            'limit' => $limit,
        ]);

        if ($response->failed()) {
            throw new RuntimeException($this->errorMessage($response->json()));
        }

        return $response->json('data', []);
    }

    public function createComment(string $postId, string $message): string
    {
        $response = $this->request()->post("/{$postId}/comments", [
            'message' => $message,
        ]);

        if ($response->failed()) {
            throw new RuntimeException($this->errorMessage($response->json()));
        }

        $commentId = $response->json('id');

        if (! is_string($commentId) || $commentId === '') {
            throw new RuntimeException('Facebook yorum ID bilgisi donmedi.');
        }

        return $commentId;
    }

    private function request(): PendingRequest
    {
        $token = config('facebook_automation.page_access_token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('FACEBOOK_PAGE_ACCESS_TOKEN tanimli degil.');
        }

        return Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->asForm()
            ->timeout(20)
            ->withToken($token);
    }

    private function baseUrl(): string
    {
        $version = trim((string) config('facebook_automation.graph_version', 'v25.0'), '/');

        return "https://graph.facebook.com/{$version}";
    }

    private function pageId(): string
    {
        $pageId = config('facebook_automation.page_id');

        if (! is_string($pageId) || $pageId === '') {
            throw new RuntimeException('FACEBOOK_PAGE_ID tanimli degil.');
        }

        return $pageId;
    }

    private function errorMessage(?array $payload): string
    {
        $message = data_get($payload, 'error.message');
        $code = data_get($payload, 'error.code');

        if (is_string($message) && $message !== '') {
            return $code ? "Facebook API hatasi ({$code}): {$message}" : "Facebook API hatasi: {$message}";
        }

        return 'Facebook API bilinmeyen hata dondurdu.';
    }
}
