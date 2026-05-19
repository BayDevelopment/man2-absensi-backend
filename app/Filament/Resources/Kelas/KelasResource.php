<?php

namespace App\Filament\Resources\Kelas;

use App\Filament\Resources\Kelas\Pages\CreateKelas;
use App\Filament\Resources\Kelas\Pages\EditKelas;
use App\Filament\Resources\Kelas\Pages\ListKelas;
use App\Filament\Resources\Kelas\Schemas\KelasForm;
use App\Filament\Resources\Kelas\Tables\KelasTable;
use App\Models\Kelas;
use App\Models\KelasModel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class KelasResource extends Resource
{
    protected static ?string $model = KelasModel::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $recordTitleAttribute = 'nama_kelas';

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
        return 5; // ganti angka sesuai urutan yang lo mau
    }
    public static function getModelLabel(): string
    {
        return 'Kelas';
    }
    public static function getPluralModelLabel(): string
    {
        return 'Kelas';
    }
    protected static ?string $navigationLabel = 'Kelas';
    protected static ?int    $navigationSort  = 1;
    // LAST ADD


    public static function form(Schema $schema): Schema
    {
        return KelasForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KelasTable::configure($table);
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
            'index' => ListKelas::route('/'),
            'create' => CreateKelas::route('/create'),
            'edit' => EditKelas::route('/{record}/edit'),
        ];
    }
}
