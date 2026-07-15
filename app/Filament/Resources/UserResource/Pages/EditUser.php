<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        UserResource::validateOrganizationRoleHasEntity($data);
        $adminRoleId = Role::query()->where('name', 'admin')->value('id');
        $selectedRoles = collect($data['roles'] ?? [])->map(fn ($role): string => (string) $role);

        if (
            auth()->id() === $this->record->id
            && $this->record->hasRole('admin')
            && filled($adminRoleId)
            && ! $selectedRoles->contains((string) $adminRoleId)
        ) {
            throw ValidationException::withMessages([
                'data.roles' => 'Kendi admin rolünü kaldıramazsın. Önce başka bir admin hesabı oluşturmalısın.',
            ]);
        }

        return $data;
    }
}
