<?php

namespace App\Filament\Resources\CorruptionReportResource\Pages;

use App\Filament\Resources\CorruptionReportResource;
use Filament\Resources\Pages\EditRecord;

class EditCorruptionReport extends EditRecord
{
    protected static string $resource = CorruptionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
