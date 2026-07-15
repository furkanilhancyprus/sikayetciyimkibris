<?php

namespace App\Console\Commands;

use App\Services\FacebookCommentAutomationService;
use Illuminate\Console\Command;

class FacebookQueueCommentCommand extends Command
{
    protected $signature = 'facebook:queue-comment {post_id : Facebook post ID}';

    protected $description = 'Facebook postu icin reklam yorumunu guvenli kuyruga/log kaydina ekler.';

    public function handle(FacebookCommentAutomationService $service): int
    {
        $log = $service->createPendingCommentForPost((string) $this->argument('post_id'));

        if (! $log) {
            $this->warn('Otomasyon kapali veya bu post icin daha once kayit var.');

            return self::SUCCESS;
        }

        $this->info("Yorum kaydi olusturuldu: #{$log->id} ({$log->status})");

        return self::SUCCESS;
    }
}
