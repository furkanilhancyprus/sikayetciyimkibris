<?php

namespace App\Console\Commands;

use App\Services\FacebookCommentAutomationService;
use App\Services\FacebookGraphClient;
use Illuminate\Console\Command;

class FacebookFetchPostsCommand extends Command
{
    protected $signature = 'facebook:fetch-posts {--limit=10 : Kontrol edilecek son post sayisi}';

    protected $description = 'Haberler KKTC sayfasindaki yeni postlari kontrol edip yorum kuyruguna ekler.';

    public function handle(FacebookGraphClient $client, FacebookCommentAutomationService $automation): int
    {
        $posts = $client->recentPagePosts((int) $this->option('limit'));
        $created = 0;

        foreach ($posts as $post) {
            $postId = data_get($post, 'id');

            if (! is_string($postId) || $postId === '') {
                continue;
            }

            if ($automation->createPendingCommentForPost($postId)) {
                $created++;
            }
        }

        $this->info("Kontrol edilen post: ".count($posts).", olusturulan yorum kaydi: {$created}");

        return self::SUCCESS;
    }
}
