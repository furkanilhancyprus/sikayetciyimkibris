<?php

namespace App\Filament\Resources;

use App\Enums\CorruptionReportStatus;
use App\Filament\Resources\CorruptionReportResource\Pages;
use App\Http\Controllers\CorruptionReportController;
use App\Models\CorruptionReport;
use App\Models\ReportMessage;
use App\Models\User;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class CorruptionReportResource extends Resource
{
    protected static ?string $model = CorruptionReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'İtiraz ve İhbarlar';

    protected static ?string $modelLabel = 'itiraz/ihbar';

    protected static ?string $pluralModelLabel = 'itiraz ve ihbarlar';

    protected static ?string $navigationGroup = 'Başvuru Yönetimi';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['entity', 'region', 'messages.user', 'evidenceFiles']);
        $user = auth()->user();

        if ($user?->hasRole('organization')) {
            $query->where('entity_id', $user->entity_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('tracking_code')
                    ->label('Takip kodu')
                    ->disabled(),
                TextInput::make('intake_type')
                    ->label('Başvuru türü')
                    ->formatStateUsing(fn (?string $state): string => self::intakeTypeLabels()[$state] ?? ($state ?? '-'))
                    ->disabled(),
                TextInput::make('issue_area')
                    ->label('Konu')
                    ->formatStateUsing(fn (?string $state): string => CorruptionReportController::issueAreas()[$state] ?? ($state ?? '-'))
                    ->disabled(),
                TextInput::make('title')
                    ->label('Başlık')
                    ->required()
                    ->disabled(fn (): bool => auth()->user()?->hasRole('organization') ?? false)
                    ->maxLength(180),
                Textarea::make('body')
                    ->label('Vatandaşın gönderdiği metin')
                    ->disabled()
                    ->columnSpanFull(),
                Textarea::make('public_body')
                    ->label('Yayınlanacak metin')
                    ->helperText('Kamuya açık detay sayfasında gösterilecek, kişisel bilgilerden arındırılmış metin. Yayınlamadan önce zorunludur.')
                    ->disabled(fn (): bool => auth()->user()?->hasRole('organization') ?? false)
                    ->maxLength(12000)
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->label('Durum')
                    ->disabled()
                    ->formatStateUsing(fn ($state): string => self::statusLabels()[$state instanceof CorruptionReportStatus ? $state->value : $state] ?? ($state?->value ?? $state ?? '-')),
                Select::make('assigned_reporter_id')
                    ->label('Atanan görevli')
                    ->options(fn () => User::role(['reporter', 'editor'])->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => ! (auth()->user()?->hasRole('organization') ?? false)),
                TextInput::make('reporter_contact')
                    ->label('İletişim bilgisi')
                    ->disabled()
                    ->visible(fn (): bool => auth()->user()?->hasAnyRole(['editor', 'legal', 'admin']) ?? false),
                Placeholder::make('message_history')
                    ->label('Kurum / ekip cevapları')
                    ->content(fn (?CorruptionReport $record): HtmlString => self::messageHistory($record))
                    ->columnSpanFull(),
                Placeholder::make('evidence_files')
                    ->label('Kanıt dosyaları')
                    ->content(fn (?CorruptionReport $record): HtmlString => self::evidenceLinks($record))
                    ->visible(fn (): bool => auth()->user()?->hasAnyRole(['admin', 'editor', 'legal', 'moderator']) ?? false)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tracking_code')
                    ->label('Takip Kodu')
                    ->searchable(),
                TextColumn::make('intake_type')
                    ->label('Tür')
                    ->formatStateUsing(fn (?string $state): string => self::intakeTypeLabels()[$state] ?? ($state ?? '-'))
                    ->badge(),
                TextColumn::make('issue_area')
                    ->label('Konu')
                    ->formatStateUsing(fn (?string $state): string => CorruptionReportController::issueAreas()[$state] ?? ($state ?? '-'))
                    ->limit(28),
                TextColumn::make('title')
                    ->label('Başlık')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('region.name')
                    ->label('Bölge')
                    ->toggleable(),
                TextColumn::make('entity.name')
                    ->label('Kurum / Belediye')
                    ->limit(28)
                    ->toggleable(),
                TextColumn::make('status.value')
                    ->label('Durum')
                    ->formatStateUsing(fn (?string $state): string => self::statusLabels()[$state] ?? ($state ?? '-'))
                    ->badge(),
                TextColumn::make('messages_count')
                    ->label('Cevap')
                    ->counts('messages'),
                TextColumn::make('created_at')
                    ->label('Geliş Tarihi')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options(fn (): array => self::statusLabels()),
                Tables\Filters\SelectFilter::make('intake_type')
                    ->label('Tür')
                    ->options(fn (): array => self::intakeTypeLabels()),
                Tables\Filters\SelectFilter::make('issue_area')
                    ->label('Konu')
                    ->options(fn (): array => CorruptionReportController::issueAreas()),
                Tables\Filters\SelectFilter::make('region_id')
                    ->label('Bölge')
                    ->options(fn () => CorruptionReportController::orderedRegionOptions())
                    ->searchable()
                    ->visible(fn (): bool => ! (auth()->user()?->hasRole('organization') ?? false)),
                Tables\Filters\SelectFilter::make('entity_id')
                    ->label('Kurum / Belediye')
                    ->options(fn () => CorruptionReportController::orderedEntityOptions())
                    ->searchable()
                    ->visible(fn (): bool => ! (auth()->user()?->hasRole('organization') ?? false)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Aç / Düzenle'),
                self::organizationResponseAction(),
                self::approvePendingResponseAction(),
                self::rejectPendingResponseAction(),
                self::transitionAction('start_review', 'İncelemeye Al'),
                self::transitionAction('request_more_info', 'Ek Bilgi İste'),
                self::transitionAction('editor_approve', 'Editör Onayı'),
                self::transitionAction('legal_approve', 'Hukuk Onayı'),
                self::transitionAction('publish', 'Yayınla'),
                self::transitionAction('reject', 'Reddet'),
            ])
            ->paginated(false)
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCorruptionReports::route('/'),
            'edit' => Pages\EditCorruptionReport::route('/{record}/edit'),
        ];
    }

    public static function transitionAction(string $action, string $label): Action
    {
        return Action::make($action)
            ->label($label)
            ->visible(fn (): bool => ! (auth()->user()?->hasRole('organization') ?? false))
            ->form([
                Textarea::make('reason')
                    ->label('İşlem notu')
                    ->required()
                    ->maxLength(2000),
            ])
            ->action(function (CorruptionReport $record, array $data) use ($action): void {
                $user = auth()->user();

                match ($action) {
                    'start_review' => $record->startReview($user, $data['reason']),
                    'request_more_info' => $record->requestMoreInfo($user, $data['reason']),
                    'editor_approve' => $record->approveByEditor($user, $data['reason']),
                    'legal_approve' => $record->approveByLegal($user, $data['reason']),
                    'publish' => $record->publish($user, $data['reason']),
                    'reject' => $record->reject($user, $data['reason']),
                };
            });
    }

    public static function organizationResponseAction(): Action
    {
        return Action::make('organization_response')
            ->label('Kurum Cevabı Yaz')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->visible(fn (CorruptionReport $record): bool => self::canOrganizationRespond($record))
            ->form([
                Textarea::make('body')
                    ->label('Cevap metni')
                    ->required()
                    ->maxLength(6000)
                    ->rows(6),
            ])
            ->action(function (CorruptionReport $record, array $data): void {
                abort_unless(self::canOrganizationRespond($record), 403);

                $record->messages()->create([
                    'sender_type' => 'team',
                    'user_id' => auth()->id(),
                    'body' => $data['body'],
                    'status' => 'pending',
                ]);

                $record->moderationLogs()->create([
                    'actor_id' => auth()->id(),
                    'action' => 'organization_response_submitted',
                    'reason' => 'Kurum cevabı onay için gönderildi.',
                ]);
            });
    }

    public static function approvePendingResponseAction(): Action
    {
        return Action::make('approve_pending_response')
            ->label('Cevabı Onayla')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (CorruptionReport $record): bool => self::canModerateResponses($record))
            ->form([
                Select::make('message_id')
                    ->label('Bekleyen cevap')
                    ->options(fn (CorruptionReport $record): array => $record->messages()
                        ->where('status', 'pending')
                        ->latest()
                        ->get()
                        ->mapWithKeys(fn (ReportMessage $message): array => [
                            $message->id => str($message->body)->limit(90)->toString(),
                        ])
                        ->all())
                    ->required(),
                Textarea::make('note')
                    ->label('Onay notu')
                    ->default('Kurum cevabı yayın için uygun bulundu.')
                    ->required()
                    ->maxLength(2000),
            ])
            ->action(function (CorruptionReport $record, array $data): void {
                $message = $record->messages()->where('status', 'pending')->findOrFail($data['message_id']);
                $message->approve(auth()->user(), $data['note']);
            });
    }

    public static function rejectPendingResponseAction(): Action
    {
        return Action::make('reject_pending_response')
            ->label('Cevabı Reddet')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (CorruptionReport $record): bool => self::canModerateResponses($record))
            ->form([
                Select::make('message_id')
                    ->label('Bekleyen cevap')
                    ->options(fn (CorruptionReport $record): array => $record->messages()
                        ->where('status', 'pending')
                        ->latest()
                        ->get()
                        ->mapWithKeys(fn (ReportMessage $message): array => [
                            $message->id => str($message->body)->limit(90)->toString(),
                        ])
                        ->all())
                    ->required(),
                Textarea::make('note')
                    ->label('Ret gerekçesi')
                    ->required()
                    ->maxLength(2000),
            ])
            ->action(function (CorruptionReport $record, array $data): void {
                $message = $record->messages()->where('status', 'pending')->findOrFail($data['message_id']);
                $message->reject(auth()->user(), $data['note']);
            });
    }

    public static function canOrganizationRespond(CorruptionReport $record): bool
    {
        $user = auth()->user();

        return ($user?->hasRole('organization') ?? false)
            && filled($user->entity_id)
            && (int) $record->entity_id === (int) $user->entity_id;
    }

    public static function canModerateResponses(CorruptionReport $record): bool
    {
        return (auth()->user()?->hasAnyRole(['admin', 'editor', 'legal', 'moderator']) ?? false)
            && $record->messages()->where('status', 'pending')->exists();
    }

    public static function messageHistory(?CorruptionReport $record): HtmlString
    {
        if (! $record?->exists || $record->messages->isEmpty()) {
            return new HtmlString('<span>Henüz cevap yazılmadı.</span>');
        }

        $items = $record->messages
            ->sortByDesc('created_at')
            ->map(function ($message): string {
                $name = $message->user?->name ?? ($message->sender_type === 'reporter' ? 'Vatandaş' : 'Ekip');
                $date = optional($message->created_at)->format('d.m.Y H:i');
                $status = match ($message->status) {
                    'pending' => 'Onay bekliyor',
                    'approved' => 'Yayında',
                    'rejected' => 'Reddedildi',
                    default => $message->status ?? '-',
                };

                return '<div style="padding:10px 0;border-bottom:1px solid #e5e7eb;">'
                    .'<strong>'.e($name).'</strong> <small>'.e($date).' - '.e($status).'</small>'
                    .'<p style="margin:6px 0 0;">'.nl2br(e($message->body)).'</p>'
                    .'</div>';
            })
            ->implode('');

        return new HtmlString($items);
    }

    public static function evidenceLinks(?CorruptionReport $record): HtmlString
    {
        if (! $record?->exists || $record->evidenceFiles->isEmpty()) {
            return new HtmlString('<span>Kanıt dosyası yüklenmemiş.</span>');
        }

        $items = $record->evidenceFiles
            ->map(function ($file): string {
                $url = route('evidence-files.download', $file);
                $size = number_format((int) $file->size_bytes / 1024, 1, ',', '.').' KB';

                return '<li><a href="'.e($url).'" target="_blank" rel="noopener">'.e($file->original_filename).'</a> <small>'.e($size).'</small></li>';
            })
            ->implode('');

        return new HtmlString('<ul style="display:grid;gap:8px;margin:0;padding-left:18px;">'.$items.'</ul>');
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            'submitted' => 'Yeni',
            'under_review' => 'İncelemede',
            'needs_more_info' => 'Ek bilgi gerekli',
            'editor_approved' => 'Editör onaylı',
            'legal_approved' => 'Hukuk onaylı',
            'published' => 'Yayınlandı',
            'rejected' => 'Reddedildi',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function intakeTypeLabels(): array
    {
        return [
            'complaint' => 'İtiraz',
            'report' => 'İhbar',
        ];
    }
}
