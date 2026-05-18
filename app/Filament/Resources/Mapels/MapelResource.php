<?php

namespace App\Filament\Resources\Mapels;

use App\Filament\Resources\Mapels\Pages\CreateMapel;
use App\Filament\Resources\Mapels\Pages\EditMapel;
use App\Filament\Resources\Mapels\Pages\ListMapels;
use App\Filament\Resources\Mapels\Schemas\MapelForm;
use App\Filament\Resources\Mapels\Tables\MapelsTable;
use App\Models\Mapel;
use App\Models\MataPelajaran;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MapelResource extends Resource
{
    protected static ?string $model = MataPelajaran::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $recordTitleAttribute = 'nama';
    // ADD
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    public static function getNavigationGroup(): ?string
    {
        return 'Master Data';
    }
    public static function getNavigationSort(): ?int
    {
        return 2; // ganti angka sesuai urutan yang lo mau
    }
    public static function getModelLabel(): string
    {
        return 'Mata Pelajaran';
    }
    public static function getPluralModelLabel(): string
    {
        return 'Mata Pelajaran';
    }
    protected static ?string $navigationLabel = 'Mata Pelajaran';
    protected static ?int    $navigationSort  = 1;
    // LAST ADD

    public static function form(Schema $schema): Schema
    {
        return MapelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MapelsTable::configure($table);
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
            'index' => ListMapels::route('/'),
            'create' => CreateMapel::route('/create'),
            'edit' => EditMapel::route('/{record}/edit'),
        ];
    }
}
