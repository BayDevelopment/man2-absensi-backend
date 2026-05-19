<?php

namespace App\Filament\Resources\GuruMapels;

use App\Filament\Resources\GuruMapels\Pages\CreateGuruMapel;
use App\Filament\Resources\GuruMapels\Pages\EditGuruMapel;
use App\Filament\Resources\GuruMapels\Pages\ListGuruMapels;
use App\Filament\Resources\GuruMapels\Schemas\GuruMapelForm;
use App\Filament\Resources\GuruMapels\Tables\GuruMapelsTable;
use App\Models\GuruMapel;
use App\Models\GuruMapelModel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GuruMapelResource extends Resource
{
    protected static ?string $model = GuruMapelModel::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $recordTitleAttribute = 'user_id';

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
        return 3; // ganti angka sesuai urutan yang lo mau
    }
    public static function getModelLabel(): string
    {
        return 'Guru Mapel';
    }
    public static function getPluralModelLabel(): string
    {
        return 'Guru Mapel';
    }
    protected static ?string $navigationLabel = 'Guru Mapel';
    protected static ?int    $navigationSort  = 1;
    // LAST ADD

    public static function form(Schema $schema): Schema
    {
        return GuruMapelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GuruMapelsTable::configure($table);
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
            'index' => ListGuruMapels::route('/'),
            'create' => CreateGuruMapel::route('/create'),
            'edit' => EditGuruMapel::route('/{record}/edit'),
        ];
    }
}
