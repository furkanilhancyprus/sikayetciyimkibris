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
            'notes' => 'Meta API tokenlari baglandiktan sonra aktif edilmelidir.',
        ]);

        $creatives = [
            [
                'name' => 'Genel tanitim',
                'comment_text' => 'Kibris\'ta yasadiginiz belediye, kurum veya vatandaslik magduriyetlerini Sikayetciyim Kibris\'ta paylasabilirsiniz.',
                'target_url' => 'https://sikayetciyimkibris.com',
            ],
            [
                'name' => 'Belediye hizmetleri',
                'comment_text' => 'Yol, asfalt, su, aydinlatma ve belediye hizmetleriyle ilgili sorunlarinizi gorunur yapmak icin Sikayetciyim Kibris\'a basvuru olusturabilirsiniz.',
                'target_url' => 'https://sikayetciyimkibris.com/ihbar',
            ],
            [
                'name' => 'Vatandaslik ve kurum magduriyeti',
                'comment_text' => 'Vatandaslik, muhaceret, kamu kurumu veya belediye hizmetlerinde yasadiginiz magduriyetleri Sikayetciyim Kibris uzerinden takip edilebilir hale getirebilirsiniz.',
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
