<?php

namespace App\Filament\Resources\Absensis\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class AbsensiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Absensi')
                    ->description('Isi data absensi siswa. Face recognition hanya tersedia di aplikasi siswa.')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([

                        Grid::make(2)->schema([

                            // ── Pilih Siswa ──────────────────────────────────
                            Select::make('siswa_id')
                                ->label('Nama Siswa')
                                ->relationship(
                                    name: 'siswa',
                                    titleAttribute: 'nama',
                                    modifyQueryUsing: fn(Builder $query) => $query->orderBy('nama')
                                )
                                ->searchable()
                                ->preload()
                                ->required()
                                ->native(false)
                                ->placeholder('Cari nama siswa...')
                                ->validationMessages([
                                    'required' => 'Nama siswa wajib dipilih.',
                                ]),

                            // ── Pilih Kelas ──────────────────────────────────
                            Select::make('kelas_id')
                                ->label('Kelas')
                                ->relationship(
                                    name: 'kelas',
                                    titleAttribute: 'nama_kelas',
                                    modifyQueryUsing: fn(Builder $query) => $query->orderBy('nama_kelas')
                                )
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->native(false)
                                ->placeholder('Pilih kelas...'),

                        ]),

                        Grid::make(2)->schema([

                            // ── Pilih Jadwal ─────────────────────────────────
                            Select::make('jadwal_id')
                                ->label('Jadwal / Mata Pelajaran')
                                ->relationship(
                                    name: 'jadwal',
                                    titleAttribute: 'nama_pelajaran',
                                    modifyQueryUsing: fn(Builder $query) => $query->orderBy('nama_pelajaran')
                                )
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->native(false)
                                ->placeholder('Pilih jadwal pelajaran...'),

                            // ── Tanggal ──────────────────────────────────────
                            DatePicker::make('tanggal')
                                ->label('Tanggal Absensi')
                                ->required()
                                ->default(now())
                                ->maxDate(now())
                                ->native(false)
                                ->displayFormat('d M Y')
                                ->validationMessages([
                                    'required' => 'Tanggal wajib diisi.',
                                    'max_date' => 'Tanggal tidak boleh melebihi hari ini.',
                                ]),

                        ]),

                        Grid::make(2)->schema([

                            // ── Jam Masuk ────────────────────────────────────
                            TimePicker::make('jam_masuk')
                                ->label('Jam Masuk')
                                ->nullable()
                                ->seconds(false)
                                ->native(false),

                            // ── Jam Keluar ───────────────────────────────────
                            TimePicker::make('jam_keluar')
                                ->label('Jam Keluar')
                                ->nullable()
                                ->seconds(false)
                                ->native(false)
                                ->after('jam_masuk')
                                ->validationMessages([
                                    'after' => 'Jam keluar harus setelah jam masuk.',
                                ]),

                        ]),

                        Grid::make(2)->schema([

                            // ── Status ───────────────────────────────────────
                            Select::make('status')
                                ->label('Status Kehadiran')
                                ->options([
                                    'hadir'    => '✅ Hadir',
                                    'terlambat' => '⏰ Terlambat',
                                    'izin'     => '📝 Izin',
                                    'sakit'    => '🏥 Sakit',
                                    'alfa'     => '❌ Alfa / Tanpa Keterangan',
                                ])
                                ->required()
                                ->default('hadir')
                                ->native(false)
                                ->validationMessages([
                                    'required' => 'Status kehadiran wajib dipilih.',
                                ]),

                            // ── Tahun Ajaran ─────────────────────────────────
                            Select::make('tahun_ajaran_id')
                                ->label('Tahun Ajaran')
                                ->relationship(
                                    name: 'tahunAjaran',
                                    titleAttribute: 'nama',
                                    modifyQueryUsing: fn(Builder $query) => $query->orderByDesc('nama')
                                )
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->native(false),

                        ]),

                        // ── Semester ─────────────────────────────────────────
                        Select::make('semester_id')
                            ->label('Semester')
                            ->relationship(
                                name: 'semester',
                                titleAttribute: 'nama',
                                modifyQueryUsing: fn(Builder $query) => $query->orderBy('nama')
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->native(false)
                            ->columnSpanFull(),

                        // ── Keterangan ───────────────────────────────────────
                        Textarea::make('keterangan')
                            ->label('Keterangan Tambahan')
                            ->placeholder('Contoh: Izin karena sakit, ada surat dokter.')
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpanFull(),

                    ]),

                // ── Info: Face Recognition tidak tersedia di form guru ────────
                Section::make('ℹ️ Informasi')
                    ->schema([
                        Placeholder::make('info_face')
                            ->label('')
                            ->content('Face recognition hanya tersedia melalui aplikasi siswa. Absensi dari halaman ini dicatat tanpa verifikasi wajah.')
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->collapsible(),
            ]);
    }
}
