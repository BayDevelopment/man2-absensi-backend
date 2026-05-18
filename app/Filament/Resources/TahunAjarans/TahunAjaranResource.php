<?php

namespace App\Filament\Resources\TahunAjarans;

use App\Filament\Resources\TahunAjarans\Pages\CreateTahunAjaran;
use App\Filament\Resources\TahunAjarans\Pages\EditTahunAjaran;
use App\Filament\Resources\TahunAjarans\Pages\ListTahunAjarans;
use App\Filament\Resources\TahunAjarans\Schemas\TahunAjaranForm;
use App\Filament\Resources\TahunAjarans\Tables\TahunAjaransTable;
use App\Models\TahunAjaran;
use App\Models\TahunAjaranModel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TahunAjaranResource extends Resource
{
    protected static ?string $model = TahunAjaranModel::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

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
        return 5; // ganti angka sesuai urutan yang lo mau
    }
    public static function getModelLabel(): string
    {
        return 'Tahun Ajaran';
    }
    public static function getPluralModelLabel(): string
    {
        return 'Tahun Ajaran';
    }
    protected static ?string $navigationLabel = 'Tahun Ajaran';
    protected static ?int    $navigationSort  = 1;

    // tambahan set untuk atur tahun ajaran dan semua non active ( hanya 1 )
    // Tambahkan method ini di dalam class resource
    protected function handleRecordCreation(array $data): Model
    {
        if (!empty($data['is_active'])) {
            TahunAjaranModel::query()->update(['is_active' => false]);
        }

        return parent::handleRecordCreation($data);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (!empty($data['is_active'])) {
            TahunAjaranModel::query()
                ->where('id', '!=', $record->id)
                ->update(['is_active' => false]);
        }

        return parent::handleRecordUpdate($record, $data);
    }
    // LAST ADD

    public static function form(Schema $schema): Schema
    {
        return TahunAjaranForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TahunAjaransTable::configure($table);
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
            'index' => ListTahunAjarans::route('/'),
            'create' => CreateTahunAjaran::route('/create'),
            'edit' => EditTahunAjaran::route('/{record}/edit'),
        ];
    }
}
