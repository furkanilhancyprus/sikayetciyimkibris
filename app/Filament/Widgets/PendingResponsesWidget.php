<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CorruptionReportResource;
use App\Models\ReportMessage;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PendingResponsesWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Onay Bekleyen Mesajlar';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'editor', 'legal', 'moderator']) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ReportMessage::query()
                    ->with(['corruptionReport.entity', 'user'])
                    ->where('status', 'pending')
                    ->latest()
                    ->limit(8)
            )
            ->columns([
                TextColumn::make('sender_type')
                    ->label('Kaynak')
                    ->formatStateUsing(fn (?string $state): string => $state === 'reporter' ? 'Vatandaş' : 'Kurum')
                    ->badge(),
                TextColumn::make('corruptionReport.tracking_code')
                    ->label('Takip Kodu')
                    ->searchable(),
                TextColumn::make('corruptionReport.title')
                    ->label('Başvuru')
                    ->limit(42),
                TextColumn::make('corruptionReport.entity.name')
                    ->label('Kurum')
                    ->limit(30),
                TextColumn::make('body')
                    ->label('Mesaj')
                    ->limit(72),
                TextColumn::make('created_at')
                    ->label('Geliş')
                    ->since(),
            ])
            ->actions([
                Tables\Actions\Action::make('open_report')
                    ->label('Dosyayı Aç')
                    ->url(fn (ReportMessage $record): string => CorruptionReportResource::getUrl('edit', ['record' => $record->corruptionReport])),
            ])
            ->paginated(false)
            ->emptyStateHeading('Onay bekleyen mesaj yok')
            ->emptyStateDescription('Kurum cevapları ve vatandaş ek açıklamaları burada sıraya düşer.');
    }
}
