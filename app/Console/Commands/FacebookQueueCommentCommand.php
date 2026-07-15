<?php

namespace App\Console\Commands;

use App\Services\FacebookCommentAutomationService;
use Illuminate\Console\Command;

class FacebookQueueCommentCommand extends Command
{
    protected $signature = 'facebook:queue-comment {post_id : Facebook post ID}';

    protected $description = 'Facebook postu için reklam yorumunu güvenli kuyruğa/log kaydına ekler.';

    public function handle(FacebookCommentAutomationService $service): int
    {
        $log = $service->createPendingCommentForPost((string) $this->argument('post_id'));

        if (! $log) {
            $this->warn('Otomasyon kapalı veya bu post için daha önce kayıt var.');

            return self::SUCCESS;
        }

        $this->info("Yorum kaydı oluşturuldu: #{$log->id} ({$log->status})");

        return self::SUCCESS;
    }
}
