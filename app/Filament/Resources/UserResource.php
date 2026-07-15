<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Http\Controllers\CorruptionReportController;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Kullanıcılar';

    protected static ?string $modelLabel = 'kullanıcı';

    protected static ?string $pluralModelLabel = 'kullanıcılar';

    protected static ?string $navigationGroup = 'Yönetim';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Ad Soyad')
                    ->required()
                    ->maxLength(160),
                TextInput::make('email')
                    ->label('E-posta')
                    ->email()
                    ->required()
                    ->rules(fn (?User $record): array => [
                        function (string $attribute, mixed $value, \Closure $fail) use ($record): void {
                            $exists = User::query()
                                ->where('email_hash', hash('sha256', mb_strtolower((string) $value)))
                                ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
                                ->exists();

                            if ($exists) {
                                $fail('Bu e-posta adresiyle kayıtlı bir kullanıcı var.');
                            }
                        },
                    ])
                    ->maxLength(255),
                TextInput::make('password')
                    ->label('Şifre')
                    ->password()
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->maxLength(255),
                Select::make('entity_id')
                    ->label('Bağlı Kurum / Kuruluş')
                    ->options(fn (): array => CorruptionReportController::orderedEntityOptions())
                    ->searchable()
                    ->preload()
                    ->helperText('Kurum hesabı için ilgili kurum/kuruluş seçili olmalı.'),
                Select::make('roles')
                    ->label('Roller')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->helperText('Kurum hesabı için organization rolü ve bağlı kurum birlikte seçilmelidir.')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Ad Soyad')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('E-posta')
                    ->searchable(),
                TextColumn::make('entity.name')
                    ->label('Kurum')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label('Roller')
                    ->formatStateUsing(fn (string $state): string => self::roleLabels()[$state] ?? $state)
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Rol')
                    ->relationship('roles', 'name'),
                Tables\Filters\SelectFilter::make('entity_id')
                    ->label('Kurum')
                    ->options(fn (): array => CorruptionReportController::orderedEntityOptions())
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->paginated(false)
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['entity', 'roles']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function roleLabels(): array
    {
        return [
            'admin' => 'Admin',
            'editor' => 'Editör',
            'legal' => 'Hukuk',
            'moderator' => 'Moderatör',
            'organization' => 'Kurum',
            'reporter' => 'Vatandaş',
            'verified_user' => 'Doğrulanmış',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function validateOrganizationRoleHasEntity(array $data): void
    {
        $roles = collect($data['roles'] ?? []);
        $organizationRoleIds = Role::query()
            ->where('name', 'organization')
            ->pluck('id')
            ->map(fn (int $id): string => (string) $id)
            ->all();

        $hasOrganizationRole = $roles
            ->map(fn ($role): string => (string) $role)
            ->intersect($organizationRoleIds)
            ->isNotEmpty();

        if ($hasOrganizationRole && blank($data['entity_id'] ?? null)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'data.entity_id' => 'Kurum rolü verilen kullanıcı için bağlı kurum/kuruluş seçilmelidir.',
            ]);
        }
    }
}
