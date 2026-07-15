<?php

namespace App\Console\Commands;

use App\Services\FacebookCommentAutomationService;
use Illuminate\Console\Command;

class FacebookPostScheduledCommentsCommand extends Command
{
    protected $signature = 'facebook:post-scheduled {--limit=5 : Bir calismada gonderilecek en fazla yorum sayisi}';

    protected $description = 'Zamani gelen Facebook yorumlarini Graph API uzerinden gonderir.';

    public function handle(FacebookCommentAutomationService $automation): int
    {
        $sent = $automation->postDueScheduledComments((int) $this->option('limit'));

        $this->info("Islenen yorum kaydi: {$sent}");

        return self::SUCCESS;
    }
}
