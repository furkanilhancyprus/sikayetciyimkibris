<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CorruptionReportResource;
use App\Http\Controllers\CorruptionReportController;
use App\Models\CorruptionReport;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestReportsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Son Gelen İtiraz ve İhbarlar';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CorruptionReport::query()
                    ->with(['region', 'entity'])
                    ->latest()
                    ->limit(8)
            )
            ->columns([
                TextColumn::make('tracking_code')
                    ->label('Takip Kodu')
                    ->searchable(),
                TextColumn::make('intake_type')
                    ->label('Tür')
                    ->formatStateUsing(fn (?string $state): string => CorruptionReportResource::intakeTypeLabels()[$state] ?? ($state ?? '-'))
                    ->badge(),
                TextColumn::make('issue_area')
                    ->label('Konu')
                    ->formatStateUsing(fn (?string $state): string => CorruptionReportController::issueAreas()[$state] ?? ($state ?? '-'))
                    ->limit(32),
                TextColumn::make('title')
                    ->label('Başlık')
                    ->limit(48)
                    ->searchable(),
                TextColumn::make('entity.name')
                    ->label('Kurum')
                    ->limit(28),
                TextColumn::make('status.value')
                    ->label('Durum')
                    ->formatStateUsing(fn (?string $state): string => CorruptionReportResource::statusLabels()[$state] ?? ($state ?? '-'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Geliş')
                    ->since(),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Aç')
                    ->url(fn (CorruptionReport $record): string => CorruptionReportResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated(false)
            ->emptyStateHeading('Henüz başvuru yok')
            ->emptyStateDescription('Vatandaşlardan itiraz veya ihbar geldikçe bu alanda görünecek.');
    }
}
