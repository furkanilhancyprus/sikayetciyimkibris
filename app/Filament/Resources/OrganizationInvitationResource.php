<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationInvitationResource\Pages;
use App\Http\Controllers\CorruptionReportController;
use App\Models\OrganizationInvitation;
use App\Notifications\OrganizationInvitationNotification;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;

class OrganizationInvitationResource extends Resource
{
    protected static ?string $model = OrganizationInvitation::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Kurum Davetleri';

    protected static ?string $modelLabel = 'kurum daveti';

    protected static ?string $pluralModelLabel = 'kurum davetleri';

    protected static ?string $navigationGroup = 'Yönetim';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('entity_id')
                    ->label('Kurum / Kuruluş')
                    ->default(fn (): ?int => request()->integer('entity_id') ?: null)
                    ->options(fn (): array => CorruptionReportController::orderedEntityOptions())
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('contact_name')
                    ->label('Yetkili adı')
                    ->maxLength(160),
                TextInput::make('invited_email')
                    ->label('Davet e-postası')
                    ->email()
                    ->required()
                    ->rules(fn (?OrganizationInvitation $record): array => [
                        function (string $attribute, mixed $value, \Closure $fail) use ($record): void {
                            $exists = OrganizationInvitation::query()
                                ->whereNull('accepted_at')
                                ->where('invited_email_hash', hash('sha256', mb_strtolower((string) $value)))
                                ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                                ->exists();

                            if ($exists) {
                                $fail('Bu e-posta için bekleyen bir kurum daveti zaten var.');
                            }
                        },
                    ])
                    ->maxLength(255),
                DateTimePicker::make('expires_at')
                    ->label('Son geçerlilik')
                    ->seconds(false)
                    ->helperText('Boş bırakılırsa davet kabul edilene kadar geçerli kalır.'),
                Placeholder::make('invite_url')
                    ->label('Özel kayıt linki')
                    ->content(function (?OrganizationInvitation $record): HtmlString {
                        if (! $record?->exists) {
                            return new HtmlString('<span>Link kayıt oluşturulduktan sonra burada görünecek.</span>');
                        }

                        $url = route('organization-invitations.show', $record->token);

                        return new HtmlString('<code style="user-select: all;">'.e($url).'</code>');
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('entity.name')
                    ->label('Kurum')
                    ->searchable(),
                TextColumn::make('contact_name')
                    ->label('Yetkili')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('invited_email')
                    ->label('E-posta')
                    ->searchable(),
                TextColumn::make('invitation_status')
                    ->label('Durum')
                    ->state(function (OrganizationInvitation $record): string {
                        if (filled($record->accepted_at)) {
                            return 'Kabul edildi';
                        }

                        if (filled($record->revoked_at)) {
                            return 'İptal edildi';
                        }

                        if (filled($record->expires_at) && $record->expires_at->isPast()) {
                            return 'Süresi doldu';
                        }

                        return 'Bekliyor';
                    })
                    ->badge(),
                TextColumn::make('expires_at')
                    ->label('Son Geçerlilik')
                    ->dateTime()
                    ->placeholder('Süresiz'),
                TextColumn::make('acceptedUser.name')
                    ->label('Açılan Hesap')
                    ->placeholder('-'),
                TextColumn::make('last_sent_at')
                    ->label('Son Gönderim')
                    ->since()
                    ->placeholder('-'),
                TextColumn::make('invite_url')
                    ->label('Link')
                    ->state(fn (OrganizationInvitation $record): string => route('organization-invitations.show', $record->token))
                    ->copyable()
                    ->limit(42),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('send_invitation')
                    ->label('Mail Gönder')
                    ->icon('heroicon-o-paper-airplane')
                    ->visible(fn (OrganizationInvitation $record): bool => $record->isUsable())
                    ->action(function (OrganizationInvitation $record): void {
                        $record->loadMissing('entity');
                        (new AnonymousNotifiable)
                            ->route('mail', $record->invited_email)
                            ->notify(new OrganizationInvitationNotification($record));

                        $record->forceFill(['last_sent_at' => now()])->save();
                        $record->moderationLogs()->create([
                            'actor_id' => auth()->id(),
                            'action' => 'organization_invitation_sent',
                            'reason' => 'Kurum davet e-postası gönderildi.',
                        ]);
                    }),
                Action::make('renew')
                    ->label('Yenile')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (OrganizationInvitation $record): bool => blank($record->accepted_at))
                    ->requiresConfirmation()
                    ->action(function (OrganizationInvitation $record): void {
                        $record->forceFill([
                            'token' => Str::random(64),
                            'expires_at' => now()->addDays((int) config('security.organization_invitation_expiry_days', 14)),
                            'revoked_at' => null,
                        ])->save();

                        $record->moderationLogs()->create([
                            'actor_id' => auth()->id(),
                            'action' => 'organization_invitation_renewed',
                            'reason' => 'Kurum davet bağlantısı yenilendi.',
                        ]);
                    }),
                Action::make('revoke')
                    ->label('İptal Et')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (OrganizationInvitation $record): bool => blank($record->accepted_at) && blank($record->revoked_at))
                    ->requiresConfirmation()
                    ->action(function (OrganizationInvitation $record): void {
                        $record->forceFill(['revoked_at' => now()])->save();

                        $record->moderationLogs()->create([
                            'actor_id' => auth()->id(),
                            'action' => 'organization_invitation_revoked',
                            'reason' => 'Kurum davet bağlantısı iptal edildi.',
                        ]);
                    }),
            ])
            ->paginated(false)
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizationInvitations::route('/'),
            'create' => Pages\CreateOrganizationInvitation::route('/create'),
            'edit' => Pages\EditOrganizationInvitation::route('/{record}/edit'),
        ];
    }
}
