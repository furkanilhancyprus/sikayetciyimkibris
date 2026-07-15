<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FacebookAutomationSettingResource\Pages;
use App\Models\FacebookAutomationSetting;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FacebookAutomationSettingResource extends Resource
{
    protected static ?string $model = FacebookAutomationSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Facebook Ayarlari';

    protected static ?string $modelLabel = 'Facebook otomasyon ayari';

    protected static ?string $pluralModelLabel = 'Facebook otomasyon ayarlari';

    protected static ?string $navigationGroup = 'Reklam Otomasyonu';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function canCreate(): bool
    {
        return FacebookAutomationSetting::query()->count() === 0;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Toggle::make('is_enabled')
                ->label('Otomasyon aktif')
                ->helperText('Meta API tokenlari baglanmadan aktif edilmemeli.'),
            Toggle::make('approval_required')
                ->label('Yorumlar once onaya dussun')
                ->helperText('Acik kalmasi onerilir.'),
            TextInput::make('page_name')
                ->label('Facebook sayfa adi')
                ->required()
                ->maxLength(160),
            TextInput::make('page_id')
                ->label('Facebook Page ID')
                ->maxLength(80),
            TextInput::make('check_interval_minutes')
                ->label('Post kontrol araligi dakika')
                ->numeric()
                ->minValue(5)
                ->required(),
            TextInput::make('min_delay_minutes')
                ->label('Minimum yorum gecikmesi dakika')
                ->numeric()
                ->minValue(0)
                ->required(),
            TextInput::make('max_delay_minutes')
                ->label('Maksimum yorum gecikmesi dakika')
                ->numeric()
                ->minValue(1)
                ->required(),
            TextInput::make('max_comments_per_hour')
                ->label('Saatlik yorum limiti')
                ->numeric()
                ->minValue(1)
                ->required(),
            TextInput::make('max_comments_per_day')
                ->label('Gunluk yorum limiti')
                ->numeric()
                ->minValue(1)
                ->required(),
            TextInput::make('same_creative_cooldown_hours')
                ->label('Ayni reklam tekrar bekleme saati')
                ->numeric()
                ->minValue(1)
                ->required(),
            Textarea::make('notes')
                ->label('Notlar')
                ->rows(4)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_enabled')->label('Aktif')->boolean(),
                IconColumn::make('approval_required')->label('Onay')->boolean(),
                TextColumn::make('page_name')->label('Sayfa'),
                TextColumn::make('page_id')->label('Page ID')->placeholder('-'),
                TextColumn::make('check_interval_minutes')->label('Kontrol dk'),
                TextColumn::make('max_comments_per_hour')->label('Saatlik limit'),
                TextColumn::make('max_comments_per_day')->label('Gunluk limit'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFacebookAutomationSettings::route('/'),
            'create' => Pages\CreateFacebookAutomationSetting::route('/create'),
            'edit' => Pages\EditFacebookAutomationSetting::route('/{record}/edit'),
        ];
    }
}
