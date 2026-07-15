<?php

namespace App\Filament\Resources\FacebookAutomationSettingResource\Pages;

use App\Filament\Resources\FacebookAutomationSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFacebookAutomationSettings extends ListRecords
{
    protected static string $resource = FacebookAutomationSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Ayar Oluştur'),
        ];
    }
}
