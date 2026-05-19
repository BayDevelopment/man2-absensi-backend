<?php

namespace App\Filament\Resources\JabatanGurus;

use App\Filament\Resources\JabatanGurus\Pages\CreateJabatanGuru;
use App\Filament\Resources\JabatanGurus\Pages\EditJabatanGuru;
use App\Filament\Resources\JabatanGurus\Pages\ListJabatanGurus;
use App\Filament\Resources\JabatanGurus\Schemas\JabatanGuruForm;
use App\Filament\Resources\JabatanGurus\Tables\JabatanGurusTable;
use App\Models\JabatanGuru;
use App\Models\JabatanModel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class JabatanGuruResource extends Resource
{
    protected static ?string $model = JabatanModel::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

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
        return 7; // ganti angka sesuai urutan yang lo mau
    }
    public static function getModelLabel(): string
    {
        return 'Jabatan Guru';
    }
    public static function getPluralModelLabel(): string
    {
        return 'Jabatan Guru';
    }
    protected static ?string $navigationLabel = 'Jabatan Guru';
    protected static ?int    $navigationSort  = 1;
    // LAST ADD

    public static function form(Schema $schema): Schema
    {
        return JabatanGuruForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JabatanGurusTable::configure($table);
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
            'index' => ListJabatanGurus::route('/'),
            'create' => CreateJabatanGuru::route('/create'),
            'edit' => EditJabatanGuru::route('/{record}/edit'),
        ];
    }
}
