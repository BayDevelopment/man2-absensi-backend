<?php

namespace App\Filament\Resources\Appearances;

use App\Filament\Resources\Appearances\Pages\CreateAppearance;
use App\Filament\Resources\Appearances\Pages\EditAppearance;
use App\Filament\Resources\Appearances\Pages\ListAppearances;
use App\Filament\Resources\Appearances\Schemas\AppearanceForm;
use App\Filament\Resources\Appearances\Tables\AppearancesTable;
use App\Models\Appearance;
use App\Models\AppearanceModel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AppearanceResource extends Resource
{
    protected static ?string $model = AppearanceModel::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $recordTitleAttribute = 'user_id';

    // ADD
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    public static function getNavigationGroup(): ?string
    {
        return 'Monitoring';
    }
    public static function getNavigationSort(): ?int
    {
        return 7; // ganti angka sesuai urutan yang lo mau
    }
    public static function getModelLabel(): string
    {
        return 'Set Tampilan';
    }
    public static function getPluralModelLabel(): string
    {
        return 'Set Tampilan';
    }
    protected static ?string $navigationLabel = 'Set Tampilan';
    protected static ?int    $navigationSort  = 1;
    // LAST ADD

    public static function form(Schema $schema): Schema
    {
        return AppearanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppearancesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppearances::route('/'),
            'create' => CreateAppearance::route('/create'),
            'edit' => EditAppearance::route('/{record}/edit'),
        ];
    }
}
