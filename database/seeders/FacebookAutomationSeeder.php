<?php

namespace Database\Seeders;

use App\Models\FacebookAdCreative;
use App\Models\FacebookAutomationSetting;
use Illuminate\Database\Seeder;

class FacebookAutomationSeeder extends Seeder
{
    public function run(): void
    {
        FacebookAutomationSetting::query()->firstOrCreate([], [
            'page_name' => 'Haberler KKTC',
            'is_enabled' => false,
            'approval_required' => true,
            'check_interval_minutes' => 15,
            'min_delay_minutes' => 5,
            'max_delay_minutes' => 20,
            'max_comments_per_hour' => 4,
            'max_comments_per_day' => 25,
            'same_creative_cooldown_hours' => 12,
            'notes' => 'Meta API tokenları bağlandıktan sonra aktif edilmelidir.',
        ]);

        $creatives = [
            [
                'name' => 'Genel tanıtım',
                'comment_text' => 'Kıbrıs’ta yaşadığınız belediye, kurum veya vatandaşlık mağduriyetlerini Şikayetçiyim Kıbrıs’ta paylaşabilirsiniz.',
                'target_url' => 'https://sikayetciyimkibris.com',
            ],
            [
                'name' => 'Belediye hizmetleri',
                'comment_text' => 'Yol, asfalt, su, aydınlatma ve belediye hizmetleriyle ilgili sorunlarınızı görünür yapmak için Şikayetçiyim Kıbrıs’a başvuru oluşturabilirsiniz.',
                'target_url' => 'https://sikayetciyimkibris.com/ihbar',
            ],
            [
                'name' => 'Vatandaşlık ve kurum mağduriyeti',
                'comment_text' => 'Vatandaşlık, muhaceret, kamu kurumu veya belediye hizmetlerinde yaşadığınız mağduriyetleri Şikayetçiyim Kıbrıs üzerinden takip edilebilir hale getirebilirsiniz.',
                'target_url' => 'https://sikayetciyimkibris.com',
            ],
        ];

        foreach ($creatives as $creative) {
            FacebookAdCreative::query()->firstOrCreate(
                ['name' => $creative['name']],
                $creative + ['is_active' => true, 'weight' => 1]
            );
        }
    }
}
