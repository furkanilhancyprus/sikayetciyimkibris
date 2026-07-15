<?php

namespace App\Filament\Resources\OrganizationInvitationResource\Pages;

use App\Filament\Resources\OrganizationInvitationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrganizationInvitations extends ListRecords
{
    protected static string $resource = OrganizationInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Davet Oluştur'),
        ];
    }
}
