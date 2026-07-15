<?php

namespace App\Filament\Resources\FacebookAdCreativeResource\Pages;

use App\Filament\Resources\FacebookAdCreativeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFacebookAdCreatives extends ListRecords
{
    protected static string $resource = FacebookAdCreativeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Reklam Oluştur'),
        ];
    }
}
