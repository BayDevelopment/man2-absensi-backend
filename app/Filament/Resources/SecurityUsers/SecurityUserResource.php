<?php

namespace App\Filament\Resources\SecurityUsers;

use App\Filament\Resources\SecurityUsers\Pages\CreateSecurityUser;
use App\Filament\Resources\SecurityUsers\Pages\EditSecurityUser;
use App\Filament\Resources\SecurityUsers\Pages\ListSecurityUsers;
use App\Filament\Resources\SecurityUsers\Schemas\SecurityUserForm;
use App\Filament\Resources\SecurityUsers\Tables\SecurityUsersTable;
use App\Models\SecurityUser;
use App\Models\SecurityUserModel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SecurityUserResource extends Resource
{
    protected static ?string $model = SecurityUserModel::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

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
        return 6; // ganti angka sesuai urutan yang lo mau
    }
    public static function getModelLabel(): string
    {
        return 'Set Security';
    }
    public static function getPluralModelLabel(): string
    {
        return 'Set Security';
    }
    protected static ?string $navigationLabel = 'Set Security';
    protected static ?int    $navigationSort  = 1;
    // LAST ADD

    public static function form(Schema $schema): Schema
    {
        return SecurityUserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SecurityUsersTable::configure($table);
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
            'index' => ListSecurityUsers::route('/'),
            'create' => CreateSecurityUser::route('/create'),
            'edit' => EditSecurityUser::route('/{record}/edit'),
        ];
    }
}
