<?php

namespace App\Filament\Resources\JamSekolahs;

use App\Filament\Resources\JamSekolahs\Pages\CreateJamSekolah;
use App\Filament\Resources\JamSekolahs\Pages\EditJamSekolah;
use App\Filament\Resources\JamSekolahs\Pages\ListJamSekolahs;
use App\Filament\Resources\JamSekolahs\Schemas\JamSekolahForm;
use App\Filament\Resources\JamSekolahs\Tables\JamSekolahsTable;
use App\Models\JamSekolah;
use App\Models\JamSekolahModel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class JamSekolahResource extends Resource
{
    protected static ?string $model = JamSekolahModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $recordTitleAttribute = 'jam_masuk';

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
        return 10; // ganti angka sesuai urutan yang lo mau
    }
    public static function getModelLabel(): string
    {
        return 'Jam Sekolah';
    }
    public static function getPluralModelLabel(): string
    {
        return 'Jam Sekolah';
    }
    protected static ?string $navigationLabel = 'Jam Sekolah';
    protected static ?int    $navigationSort  = 1;
    // LAST ADD

    public static function form(Schema $schema): Schema
    {
        return JamSekolahForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JamSekolahsTable::configure($table);
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
            'index' => ListJamSekolahs::route('/'),
            'create' => CreateJamSekolah::route('/create'),
            'edit' => EditJamSekolah::route('/{record}/edit'),
        ];
    }
}
