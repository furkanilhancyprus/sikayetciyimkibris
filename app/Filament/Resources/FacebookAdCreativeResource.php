<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FacebookAdCreativeResource\Pages;
use App\Models\FacebookAdCreative;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FacebookAdCreativeResource extends Resource
{
    protected static ?string $model = FacebookAdCreative::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Facebook Reklamlari';

    protected static ?string $modelLabel = 'Facebook reklami';

    protected static ?string $pluralModelLabel = 'Facebook reklamlari';

    protected static ?string $navigationGroup = 'Reklam Otomasyonu';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Toggle::make('is_active')
                ->label('Aktif')
                ->default(true),
            TextInput::make('name')
                ->label('Reklam adi')
                ->required()
                ->maxLength(160),
            Textarea::make('comment_text')
                ->label('Yorum metni')
                ->helperText('Facebook postunun altina yazilacak metin. Ayni metni surekli kullanmamak icin birden fazla reklam olustur.')
                ->required()
                ->rows(6)
                ->maxLength(2000)
                ->columnSpanFull(),
            TextInput::make('target_url')
                ->label('Link')
                ->url()
                ->maxLength(500),
            TextInput::make('image_url')
                ->label('Gorsel URL')
                ->url()
                ->helperText('Facebook yorum API destegi ve izin durumuna gore ileride kullanilacak. Simdilik reklam kaydi icin saklanir.')
                ->maxLength(500),
            TextInput::make('weight')
                ->label('Agirlik')
                ->numeric()
                ->minValue(1)
                ->default(1)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('name')->label('Ad')->searchable(),
                TextColumn::make('comment_text')->label('Metin')->limit(70)->searchable(),
                TextColumn::make('target_url')->label('Link')->limit(32),
                TextColumn::make('weight')->label('Agirlik')->sortable(),
                TextColumn::make('last_used_at')->label('Son kullanim')->since()->placeholder('-'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFacebookAdCreatives::route('/'),
            'create' => Pages\CreateFacebookAdCreative::route('/create'),
            'edit' => Pages\EditFacebookAdCreative::route('/{record}/edit'),
        ];
    }
}
