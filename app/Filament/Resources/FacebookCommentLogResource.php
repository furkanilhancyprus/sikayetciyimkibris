<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FacebookCommentLogResource\Pages;
use App\Models\FacebookCommentLog;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FacebookCommentLogResource extends Resource
{
    protected static ?string $model = FacebookCommentLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Facebook Yorum Logları';

    protected static ?string $modelLabel = 'Facebook yorum logu';

    protected static ?string $pluralModelLabel = 'Facebook yorum logları';

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
                TextColumn::make('created_at')->label('Kayıt')->dateTime()->sortable(),
                TextColumn::make('status')->label('Durum')->badge()->searchable(),
                TextColumn::make('creative.name')->label('Reklam')->placeholder('-')->limit(28),
                TextColumn::make('facebook_post_id')->label('Post ID')->searchable()->limit(24),
                TextColumn::make('facebook_comment_id')->label('Yorum ID')->placeholder('-')->limit(24),
                TextColumn::make('scheduled_at')->label('Plan')->dateTime()->placeholder('-'),
                TextColumn::make('posted_at')->label('Gönderim')->dateTime()->placeholder('-'),
                TextColumn::make('error_message')->label('Hata')->limit(48)->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        'pending' => 'Beklemede',
                        'scheduled' => 'Planlandı',
                        'posted' => 'Gönderildi',
                        'failed' => 'Hata',
                        'skipped' => 'Atlandı',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Gör'),
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
