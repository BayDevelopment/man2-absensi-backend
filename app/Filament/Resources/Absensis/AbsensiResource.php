<?php

namespace App\Filament\Resources\Absensis;

use App\Filament\Resources\Absensis\Pages\CreateAbsensi;
use App\Filament\Resources\Absensis\Pages\EditAbsensi;
use App\Filament\Resources\Absensis\Pages\ListAbsensis;
use App\Filament\Resources\Absensis\Schemas\AbsensiForm;
use App\Filament\Resources\Absensis\Tables\AbsensisTable;
use App\Models\Absensi;
use App\Models\AbsensiModel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AbsensiResource extends Resource
{
    protected static ?string $model = AbsensiModel::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $recordTitleAttribute = 'siswa_id';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    public static function getNavigationGroup(): ?string
    {
        return 'Aktivitas';
    }
    public static function getNavigationSort(): ?int
    {
        return 2; // ganti angka sesuai urutan yang lo mau
    }
    public static function getModelLabel(): string
    {
        return 'Absensi';
    }
    public static function getPluralModelLabel(): string
    {
        return 'Data Absensi';
    }
    protected static ?string $navigationLabel = 'Absensi';
    protected static ?int    $navigationSort  = 1;
    // LAST ADD

    public static function form(Schema $schema): Schema
    {
        return AbsensiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AbsensisTable::configure($table);
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
            'index' => ListAbsensis::route('/'),
            'create' => CreateAbsensi::route('/create'),
            'edit' => EditAbsensi::route('/{record}/edit'),
        ];
    }
}
