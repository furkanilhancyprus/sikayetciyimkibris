<?php

namespace App\Filament\Resources\CorruptionReportResource\Pages;

use App\Filament\Resources\CorruptionReportResource;
use Filament\Resources\Pages\ListRecords;

class ListCorruptionReports extends ListRecords
{
    protected static string $resource = CorruptionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
