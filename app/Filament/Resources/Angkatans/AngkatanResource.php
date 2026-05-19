<?php

namespace App\Filament\Resources\Angkatans;

use App\Filament\Resources\Angkatans\Pages\CreateAngkatan;
use App\Filament\Resources\Angkatans\Pages\EditAngkatan;
use App\Filament\Resources\Angkatans\Pages\ListAngkatans;
use App\Filament\Resources\Angkatans\Schemas\AngkatanForm;
use App\Filament\Resources\Angkatans\Tables\AngkatansTable;
use App\Models\Angkatan;
use App\Models\AngkatanModel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AngkatanResource extends Resource
{
    protected static ?string $model = AngkatanModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

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
        return 8; // ganti angka sesuai urutan yang lo mau
    }
    public static function getModelLabel(): string
    {
        return 'Angkatan';
    }
    public static function getPluralModelLabel(): string
    {
        return 'Angkatan';
    }
    protected static ?string $navigationLabel = 'Angkatan';
    protected static ?int    $navigationSort  = 1;
    // LAST ADD

    public static function form(Schema $schema): Schema
    {
        return AngkatanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AngkatansTable::configure($table);
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
            'index' => ListAngkatans::route('/'),
            'create' => CreateAngkatan::route('/create'),
            'edit' => EditAngkatan::route('/{record}/edit'),
        ];
    }
}
