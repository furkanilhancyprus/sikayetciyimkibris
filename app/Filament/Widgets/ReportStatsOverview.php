<?php

namespace App\Filament\Widgets;

use App\Enums\CorruptionReportStatus;
use App\Models\CorruptionReport;
use App\Models\ReportMessage;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReportStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        return [
            Stat::make('Toplam Başvuru', CorruptionReport::query()->count())
                ->description('Sisteme gelen tüm itiraz ve ihbarlar')
                ->color('gray'),
            Stat::make('Yeni Gelen', CorruptionReport::query()->where('status', CorruptionReportStatus::Submitted)->count())
                ->description('Henüz incelemeye alınmadı')
                ->color('warning'),
            Stat::make('İncelemede', CorruptionReport::query()->where('status', CorruptionReportStatus::UnderReview)->count())
                ->description('Ekip tarafından değerlendiriliyor')
                ->color('info'),
            Stat::make('Yayınlanan', CorruptionReport::query()->where('status', CorruptionReportStatus::Published)->count())
                ->description('Herkese açık listede görünüyor')
                ->color('success'),
            Stat::make('Onay Bekleyen Mesaj', ReportMessage::query()->where('status', 'pending')->count())
                ->description('Kurum cevabı veya vatandaş ek açıklaması')
                ->color('danger'),
        ];
    }
}
