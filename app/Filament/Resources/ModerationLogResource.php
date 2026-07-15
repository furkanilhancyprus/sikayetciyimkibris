<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ModerationLogResource\Pages;
use App\Models\ModerationLog;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ModerationLogResource extends Resource
{
    protected static ?string $model = ModerationLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'İşlem Logları';

    protected static ?string $modelLabel = 'işlem logu';

    protected static ?string $pluralModelLabel = 'işlem logları';

    protected static ?string $navigationGroup = 'Yönetim';

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

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('action')->label('İşlem')->disabled(),
            TextInput::make('actor.name')->label('Kullanıcı')->disabled(),
            Textarea::make('reason')->label('Not')->disabled()->columnSpanFull(),
            Placeholder::make('created_at')->label('Tarih')->content(fn (?ModerationLog $record): string => optional($record?->created_at)->format('d.m.Y H:i') ?? '-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Tarih')->dateTime()->sortable(),
                TextColumn::make('actor.name')->label('Kullanıcı')->placeholder('Sistem')->searchable(),
                TextColumn::make('action')->label('İşlem')->badge()->searchable(),
                TextColumn::make('loggable_type')->label('Kayıt Türü')->formatStateUsing(fn (?string $state): string => class_basename((string) $state))->badge(),
                TextColumn::make('loggable_id')->label('Kayıt ID'),
                TextColumn::make('reason')->label('Not')->limit(80)->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label('İşlem')
                    ->options(fn (): array => ModerationLog::query()->orderBy('action')->pluck('action', 'action')->all()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Gör'),
            ])
            ->paginated(false)
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('actor')->latest();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModerationLogs::route('/'),
            'view' => Pages\ViewModerationLog::route('/{record}'),
        ];
    }
}
