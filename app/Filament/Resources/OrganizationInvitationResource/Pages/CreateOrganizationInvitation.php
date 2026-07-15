<?php

namespace App\Filament\Resources\OrganizationInvitationResource\Pages;

use App\Filament\Resources\OrganizationInvitationResource;
use App\Notifications\OrganizationInvitationNotification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Notifications\AnonymousNotifiable;

class CreateOrganizationInvitation extends CreateRecord
{
    protected static string $resource = OrganizationInvitationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->loadMissing('entity');

        (new AnonymousNotifiable)
            ->route('mail', $this->record->invited_email)
            ->notify(new OrganizationInvitationNotification($this->record));

        $this->record->forceFill(['last_sent_at' => now()])->save();

        $this->record->moderationLogs()->create([
            'actor_id' => auth()->id(),
            'action' => 'organization_invitation_created',
            'reason' => 'Kurum daveti oluşturuldu ve e-posta gönderildi.',
        ]);
    }
}
