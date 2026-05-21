<?php

namespace App\Filament\Resources\HariLiburs;

use App\Filament\Resources\HariLiburs\Pages\CreateHariLibur;
use App\Filament\Resources\HariLiburs\Pages\EditHariLibur;
use App\Filament\Resources\HariLiburs\Pages\ListHariLiburs;
use App\Filament\Resources\HariLiburs\Schemas\HariLiburForm;
use App\Filament\Resources\HariLiburs\Tables\HariLibursTable;
use App\Models\HariLibur;
use App\Models\HariLiburModel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HariLiburResource extends Resource
{
    protected static ?string $model = HariLiburModel::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sun';

    protected static ?string $recordTitleAttribute = 'jenis';

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
        return 4; // ganti angka sesuai urutan yang lo mau
    }
    public static function getModelLabel(): string
    {
        return 'Hari Libur';
    }
    public static function getPluralModelLabel(): string
    {
        return 'Hari Libur';
    }
    protected static ?string $navigationLabel = 'Hari Libur';
    protected static ?int    $navigationSort  = 1;
    // LAST ADD

    public static function form(Schema $schema): Schema
    {
        return HariLiburForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HariLibursTable::configure($table);
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
            'index' => ListHariLiburs::route('/'),
            'create' => CreateHariLibur::route('/create'),
            'edit' => EditHariLibur::route('/{record}/edit'),
        ];
    }
}
