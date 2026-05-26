<?php

namespace App\Filament\Resources\Absensis;

use App\Filament\Resources\Absensis\Pages\CreateAbsensi;
use App\Filament\Resources\Absensis\Pages\EditAbsensi;
use App\Filament\Resources\Absensis\Pages\ListAbsensis;
use App\Filament\Resources\Absensis\Schemas\AbsensiForm;
use App\Filament\Resources\Absensis\Tables\AbsensisTable;
use App\Models\Absensi;
use App\Models\AbsensiModel;
use App\Models\JadwalModel;
use BackedEnum;
use Carbon\Carbon;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AbsensiResource extends Resource
{
    protected static ?string $model = AbsensiModel::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $recordTitleAttribute = 'siswa_id';

    public static function getJamKeluarOtomatis(?int $jadwalId, ?int $kelasId, ?string $tanggal): ?string
    {
        if (!$kelasId || !$tanggal) return null;

        $carbon = Carbon::parse($tanggal);
        $hariId = static::namaHariIndonesia($carbon);
        $hariEn = $carbon->format('l');

        $mapelTerakhir = JadwalModel::where('kelas_id', $kelasId)
            ->whereIn('hari', [
                $hariId,
                strtolower($hariId),
                strtoupper($hariId),
                $hariEn,
                strtolower($hariEn),
                strtoupper($hariEn),
            ])
            ->where(fn($q) => $q->where('is_break', false)->orWhereNull('is_break'))
            ->whereNotNull('mata_pelajaran_id')
            ->orderByRaw('COALESCE(urutan, 999) DESC')
            ->orderBy('jam_selesai', 'desc')
            ->first();

        return $mapelTerakhir?->jam_selesai
            ? substr($mapelTerakhir->jam_selesai, 0, 5)
            : null;
    }

    public static function namaHariIndonesia(Carbon $date): string
    {
        return [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ][$date->dayOfWeekIso] ?? $date->format('l');
    }

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
