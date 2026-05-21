<?php

namespace App\Filament\Resources\Pengaturans;

use App\Filament\Resources\Pengaturans\Pages\CreatePengaturan;
use App\Filament\Resources\Pengaturans\Pages\EditPengaturan;
use App\Filament\Resources\Pengaturans\Pages\ListPengaturans;
use App\Filament\Resources\Pengaturans\Schemas\PengaturanForm;
use App\Filament\Resources\Pengaturans\Tables\PengaturansTable;
use App\Models\Pengaturan;
use App\Models\PengaturanModel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PengaturanResource extends Resource
{
    protected static ?string $model = PengaturanModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $recordTitleAttribute = 'nama_sekolah';

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
        return 2; // ganti angka sesuai urutan yang lo mau
    }
    public static function getModelLabel(): string
    {
        return 'Pengaturan';
    }
    public static function getPluralModelLabel(): string
    {
        return 'Pengaturan';
    }
    protected static ?string $navigationLabel = 'Pengaturan';
    protected static ?int    $navigationSort  = 1;
    // LAST ADD

    public static function form(Schema $schema): Schema
    {
        return PengaturanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PengaturansTable::configure($table);
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
            'index' => ListPengaturans::route('/'),
            'create' => CreatePengaturan::route('/create'),
            'edit' => EditPengaturan::route('/{record}/edit'),
        ];
    }
}
