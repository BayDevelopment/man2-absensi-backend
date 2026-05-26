<?php

namespace App\Filament\Resources\Absensis\Schemas;

use App\Models\JadwalModel;
use App\Models\JamSekolahModel;
use App\Models\KelasModel;
use App\Models\SiswaModel;
use App\Models\AbsensiModel;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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

                            Select::make('siswa_id')
                                ->label('Nama Siswa')
                                ->relationship(
                                    name: 'siswa',
                                    titleAttribute: 'nama_lengkap',
                                    modifyQueryUsing: fn(Builder $query) => $query->orderBy('nama_lengkap')
                                )
                                ->searchable()
                                ->preload()
                                ->required()
                                ->native(false)
                                ->placeholder('Cari nama siswa...')
                                ->validationMessages([
                                    'required' => 'Nama siswa wajib dipilih.',
                                ]),

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

                            Select::make('jadwal_id')
                                ->label('Jadwal / Mata Pelajaran')
                                ->relationship(
                                    name: 'jadwal',
                                    titleAttribute: 'id',
                                    modifyQueryUsing: fn(Builder $query) => $query
                                        ->join('mata_pelajarans', 'jadwals.mata_pelajaran_id', '=', 'mata_pelajarans.id')
                                        ->select('jadwals.*', 'mata_pelajarans.nama as mp_nama')
                                        ->orderBy('mata_pelajarans.nama')
                                )
                                ->getOptionLabelFromRecordUsing(
                                    fn(JadwalModel $record) => ($record->mataPelajaran?->nama ?? $record->mp_nama ?? '-') .
                                        " | {$record->hari} " .
                                        substr($record->jam_mulai ?? '', 0, 5) . '-' .
                                        substr($record->jam_selesai ?? '', 0, 5)
                                )
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->native(false)
                                ->placeholder('Pilih jadwal pelajaran...'),

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

                            Select::make('status')
                                ->label('Status Kehadiran')
                                ->options([
                                    'hadir'     => '✅ Hadir',
                                    'terlambat' => '⏰ Terlambat',
                                    'izin'      => '📝 Izin',
                                    'sakit'     => '🏥 Sakit',
                                    'alfa'      => '❌ Alfa / Tanpa Keterangan',
                                ])
                                ->required()
                                ->default('hadir')
                                ->native(false)
                                ->live()
                                ->afterStateUpdated(function (string $state, Set $set) {
                                    if (in_array($state, ['izin', 'sakit', 'alfa'])) {
                                        $set('jam_masuk', null);
                                        $set('jam_keluar', null);
                                    }
                                })
                                ->validationMessages([
                                    'required' => 'Status kehadiran wajib dipilih.',
                                ]),

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

                        Grid::make(2)->schema([

                            TimePicker::make('jam_masuk')
                                ->label('Jam Masuk')
                                ->nullable()
                                ->seconds(false)
                                ->native(false)
                                ->visible(fn(Get $get) => in_array($get('status'), ['hadir', 'terlambat'])),

                            TimePicker::make('jam_keluar')
                                ->label('Jam Keluar')
                                ->nullable()
                                ->seconds(false)
                                ->native(false)
                                ->after('jam_masuk')
                                ->helperText('Kosongkan untuk otomatis terisi dari jam selesai mapel terakhir.')
                                ->visible(fn(Get $get) => in_array($get('status'), ['hadir', 'terlambat']))
                                ->validationMessages([
                                    'after' => 'Jam keluar harus setelah jam masuk.',
                                ]),

                        ]),

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

                        Textarea::make('keterangan')
                            ->label('Keterangan Tambahan')
                            ->placeholder('Contoh: Izin karena sakit, ada surat dokter.')
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpanFull(),

                    ]),

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

    // =========================================================================
    // SCHEMA ABSEN MASSAL (dipakai di modal header action)
    // =========================================================================
    public static function absenMassal(): array
    {
        return [
            Section::make('Pengaturan Absensi')
                ->schema([
                    Grid::make(2)->schema([

                        Select::make('kelas_id')
                            ->label('Kelas')
                            ->options(KelasModel::orderBy('nama_kelas')->pluck('nama_kelas', 'id'))
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('jadwal_id', null);
                                $set('siswa_absensi', []);
                            }),

                        Select::make('jadwal_id')
                            ->label('Jadwal / Mata Pelajaran')
                            ->options(function (Get $get) {
                                $kelasId = $get('kelas_id');
                                if (!$kelasId) return [];

                                return \App\Models\JadwalModel::with('mataPelajaran')
                                    ->where('kelas_id', $kelasId)
                                    ->whereNotNull('mata_pelajaran_id')
                                    ->where(fn($q) => $q->where('is_break', false)->orWhereNull('is_break'))
                                    ->orderBy('jam_mulai')
                                    ->get()
                                    ->mapWithKeys(fn($j) => [
                                        $j->id => ($j->mataPelajaran?->nama ?? '-') .
                                            ' | ' . $j->hari .
                                            ' ' . substr($j->jam_mulai ?? '', 0, 5) .
                                            '-' . substr($j->jam_selesai ?? '', 0, 5),
                                    ]);
                            })
                            ->required()
                            ->native(false)
                            ->live()
                            ->searchable()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                static::loadSiswaKeRepeater($get, $set);
                            }),

                        DatePicker::make('tanggal')
                            ->label('Tanggal Absensi')
                            ->required()
                            ->default(today())
                            ->maxDate(today())
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                static::loadSiswaKeRepeater($get, $set);
                            }),

                        Select::make('status_default')
                            ->label('Status Default')
                            ->options([
                                'hadir'     => '✅ Hadir',
                                'terlambat' => '⚠️ Terlambat',
                                'alfa'      => '❌ Alfa',
                            ])
                            ->default('hadir')
                            ->required()
                            ->native(false)
                            ->live()
                            ->helperText('Semua siswa akan ditandai status ini. Edit per-siswa di bawah jika berbeda.')
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $existing  = $get('siswa_absensi') ?? [];
                                $newStatus = $get('status_default');
                                $set('siswa_absensi', array_map(
                                    fn($item) => array_merge($item, ['status' => $newStatus]),
                                    $existing
                                ));
                            }),

                        TimePicker::make('jam_masuk_default')
                            ->label('Jam Masuk Default')
                            ->seconds(false)
                            ->native(false)
                            ->default(function () {
                                $js = JamSekolahModel::where('aktif', 1)->first();
                                return $js?->jam_masuk ? substr($js->jam_masuk, 0, 5) : '07:00';
                            })
                            ->required()
                            ->helperText('Dipakai untuk semua siswa berstatus Hadir/Terlambat.'),

                    ]),
                ]),

            Section::make('Data Per Siswa')
                ->description('Daftar siswa otomatis muncul setelah kelas dan jadwal dipilih.')
                ->schema([
                    Repeater::make('siswa_absensi')
                        ->label('')
                        ->schema([
                            Hidden::make('siswa_id'),

                            Placeholder::make('nama_siswa')
                                ->label('Siswa')
                                ->content(
                                    fn(Get $get): string =>
                                    SiswaModel::find($get('siswa_id'))?->nama_lengkap ?? '—'
                                ),

                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    'hadir'     => '✅ Hadir',
                                    'terlambat' => '⚠️ Terlambat',
                                    'izin'      => '📋 Izin',
                                    'sakit'     => '🏥 Sakit',
                                    'alfa'      => '❌ Alfa',
                                ])
                                ->required()
                                ->native(false),

                            TimePicker::make('jam_masuk')
                                ->label('Jam Masuk')
                                ->seconds(false)
                                ->native(false)
                                ->nullable(),

                            TextInput::make('keterangan')
                                ->label('Keterangan')
                                ->nullable()
                                ->placeholder('Opsional'),
                        ])
                        ->columns(4)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->default([]),
                ]),
        ];
    }

    // =========================================================================
    // LOAD SISWA KE REPEATER
    // =========================================================================
    public static function loadSiswaKeRepeater(Get $get, Set $set): void
    {
        $kelasId         = $get('kelas_id');
        $jadwalId        = $get('jadwal_id');
        $tanggal         = $get('tanggal');
        $statusDefault   = $get('status_default') ?? 'hadir';
        $jamMasukDefault = $get('jam_masuk_default') ?? '07:00';

        if (!$kelasId || !$jadwalId) {
            $set('siswa_absensi', []);
            return;
        }

        $siswas = SiswaModel::where('kelas_id', $kelasId)
            ->where('is_active', true)
            ->orderBy('nama_lengkap')
            ->get();

        $rows = $siswas->map(function (SiswaModel $siswa) use (
            $jadwalId,
            $tanggal,
            $statusDefault,
            $jamMasukDefault
        ) {
            $existing = $tanggal
                ? AbsensiModel::where('siswa_id', $siswa->id)
                ->where('jadwal_id', $jadwalId)
                ->whereDate('tanggal', $tanggal)
                ->first()
                : null;

            return [
                'siswa_id'   => $siswa->id,
                'nama_siswa' => $siswa->nama_lengkap,
                'status'     => $existing?->status ?? $statusDefault,
                'jam_masuk'  => $existing?->jam_masuk
                    ? substr($existing->jam_masuk, 0, 5)
                    : ($statusDefault !== 'alfa' ? $jamMasukDefault : null),
                'keterangan' => $existing?->keterangan ?? null,
            ];
        })->toArray();

        $set('siswa_absensi', $rows);
    }
}
