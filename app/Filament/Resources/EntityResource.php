<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EntityResource\Pages;
use App\Http\Controllers\CorruptionReportController;
use App\Models\Entity;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EntityResource extends Resource
{
    protected static ?string $model = Entity::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Kurum ve Kuruluşlar';

    protected static ?string $modelLabel = 'kurum/kuruluş';

    protected static ?string $pluralModelLabel = 'kurum ve kuruluşlar';

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
                    ->label('Ad')
                    ->required()
                    ->maxLength(255),
                TextInput::make('category')
                    ->label('Kategori')
                    ->required()
                    ->maxLength(255),
                Select::make('region_id')
                    ->label('Bölge')
                    ->options(fn (): array => CorruptionReportController::orderedRegionOptions())
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Ad')->searchable()->sortable(),
                TextColumn::make('category')->label('Kategori')->searchable()->sortable(),
                TextColumn::make('region.name')->label('Bölge')->placeholder('-'),
                TextColumn::make('users_count')->label('Kullanıcı')->counts('users'),
                TextColumn::make('corruption_reports_count')->label('Başvuru')->counts('corruptionReports'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategori')
                    ->options(fn (): array => Entity::query()->orderBy('category')->pluck('category', 'category')->all()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('invite')
                    ->label('Davet Oluştur')
                    ->icon('heroicon-o-envelope')
                    ->url(fn (Entity $record): string => OrganizationInvitationResource::getUrl('create', ['entity_id' => $record->id])),
            ])
            ->paginated(false)
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEntities::route('/'),
            'create' => Pages\CreateEntity::route('/create'),
            'edit' => Pages\EditEntity::route('/{record}/edit'),
        ];
    }
}
