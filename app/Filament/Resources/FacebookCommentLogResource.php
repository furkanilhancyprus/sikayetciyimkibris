<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FacebookCommentLogResource\Pages;
use App\Models\FacebookCommentLog;
use App\Services\FacebookCommentAutomationService;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FacebookCommentLogResource extends Resource
{
    protected static ?string $model = FacebookCommentLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Facebook Yorum Loglari';

    protected static ?string $modelLabel = 'Facebook yorum logu';

    protected static ?string $pluralModelLabel = 'Facebook yorum loglari';

    protected static ?string $navigationGroup = 'Reklam Otomasyonu';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('status')->label('Durum')->disabled(),
            TextInput::make('creative.name')->label('Reklam')->disabled(),
            TextInput::make('facebook_post_id')->label('Post ID')->disabled(),
            TextInput::make('facebook_comment_id')->label('Yorum ID')->disabled(),
            Textarea::make('message')->label('Mesaj')->disabled()->columnSpanFull(),
            Textarea::make('error_message')->label('Hata')->disabled()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Kayit')->dateTime()->sortable(),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'scheduled' => 'info',
                        'posted' => 'success',
                        'failed' => 'danger',
                        'skipped' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Onay bekliyor',
                        'scheduled' => 'Planlandi',
                        'posted' => 'Gonderildi',
                        'failed' => 'Hata',
                        'skipped' => 'Atlandi',
                        default => $state,
                    })
                    ->searchable(),
                TextColumn::make('creative.name')->label('Reklam')->placeholder('-')->limit(28),
                TextColumn::make('facebook_post_id')->label('Post ID')->searchable()->limit(24),
                TextColumn::make('facebook_comment_id')->label('Yorum ID')->placeholder('-')->limit(24),
                TextColumn::make('scheduled_at')->label('Plan')->dateTime()->placeholder('-'),
                TextColumn::make('posted_at')->label('Gonderim')->dateTime()->placeholder('-'),
                TextColumn::make('error_message')->label('Hata')->limit(48)->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        'pending' => 'Onay bekliyor',
                        'scheduled' => 'Planlandi',
                        'posted' => 'Gonderildi',
                        'failed' => 'Hata',
                        'skipped' => 'Atlandi',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Onayla')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (FacebookCommentLog $record): bool => $record->status === 'pending')
                    ->action(function (FacebookCommentLog $record): void {
                        app(FacebookCommentAutomationService::class)->approve($record);
                        Notification::make()->title('Yorum planlandi')->success()->send();
                    }),
                Tables\Actions\Action::make('post_now')
                    ->label('Simdi Gonder')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (FacebookCommentLog $record): bool => $record->status === 'scheduled')
                    ->action(function (FacebookCommentLog $record): void {
                        app(FacebookCommentAutomationService::class)->postComment($record);
                        Notification::make()->title('Yorum islemi calistirildi')->success()->send();
                    }),
                Tables\Actions\Action::make('retry')
                    ->label('Tekrar Dene')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (FacebookCommentLog $record): bool => $record->status === 'failed')
                    ->action(function (FacebookCommentLog $record): void {
                        app(FacebookCommentAutomationService::class)->retry($record);
                        Notification::make()->title('Yorum yeniden planlandi')->success()->send();
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('Iptal')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (FacebookCommentLog $record): bool => in_array($record->status, ['pending', 'scheduled', 'failed'], true))
                    ->action(function (FacebookCommentLog $record): void {
                        app(FacebookCommentAutomationService::class)->cancel($record);
                        Notification::make()->title('Yorum iptal edildi')->success()->send();
                    }),
                Tables\Actions\ViewAction::make()->label('Gor'),
            ])
            ->paginated(false);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('creative')->latest();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFacebookCommentLogs::route('/'),
            'view' => Pages\ViewFacebookCommentLog::route('/{record}'),
        ];
    }
}
