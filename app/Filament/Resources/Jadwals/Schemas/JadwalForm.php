<?php

namespace App\Filament\Resources\Jadwals\Schemas;

use App\Models\GuruModel;
use App\Models\JadwalModel;
use App\Models\KelasModel;
use App\Models\MataPelajaran;
use App\Models\SemesterModel;
use App\Models\TahunAjaranModel;
use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Carbon\Carbon;

class JadwalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Akademik')
                    ->description('Pilih tahun ajaran, semester, dan kelas untuk jadwal ini.')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Select::make('tahun_ajaran_id')
                            ->label('Tahun Ajaran')
                            ->relationship('tahunAjaran', 'nama')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->disabled(
                                static fn(): bool => !TahunAjaranModel::query()->exists()
                            )
                            ->helperText(
                                static fn(): string => TahunAjaranModel::query()->exists()
                                    ? 'Pilih tahun ajaran yang berlaku untuk jadwal ini.'
                                    : '⚠️ Belum ada data tahun ajaran. Tambahkan tahun ajaran terlebih dahulu.'
                            )
                            ->rules(['nullable', 'integer', 'exists:tahun_ajarans,id'])
                            ->validationMessages([
                                'exists' => 'Tahun ajaran tidak ditemukan.',
                            ]),

                        Select::make('semester_id')
                            ->label('Semester')
                            ->relationship('semester', 'nama')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->disabled(
                                static fn(): bool => !SemesterModel::query()->exists()
                            )
                            ->helperText(
                                static fn(): string => SemesterModel::query()->exists()
                                    ? 'Pilih semester yang berlaku untuk jadwal ini.'
                                    : '⚠️ Belum ada data semester. Tambahkan semester terlebih dahulu.'
                            )
                            ->rules(['nullable', 'integer', 'exists:semesters,id'])
                            ->validationMessages([
                                'exists' => 'Semester tidak ditemukan.',
                            ]),

                        Select::make('kelas_id')
                            ->label('Kelas')
                            ->relationship('kelas', 'nama_kelas')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->disabled(
                                static fn(): bool => !KelasModel::query()->exists()
                            )
                            ->helperText(
                                static fn(): string => KelasModel::query()->exists()
                                    ? 'Pilih kelas yang akan mendapat jadwal ini.'
                                    : '⚠️ Belum ada data kelas. Tambahkan kelas terlebih dahulu.'
                            )
                            ->rules(['required', 'integer', 'exists:kelas,id'])
                            ->validationMessages([
                                'required' => 'Kelas wajib dipilih.',
                                'exists'   => 'Kelas tidak ditemukan.',
                            ]),

                        // ✅ DatePicker tanggal — auto-fill hari
                        DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set) {
                                if ($state) {
                                    $hariMap = [
                                        'Monday'    => 'Senin',
                                        'Tuesday'   => 'Selasa',
                                        'Wednesday' => 'Rabu',
                                        'Thursday'  => 'Kamis',
                                        'Friday'    => 'Jumat',
                                        'Saturday'  => 'Sabtu',
                                    ];

                                    $englishDay = Carbon::parse($state)->englishDayOfWeek;
                                    $set('hari', $hariMap[$englishDay] ?? null);
                                } else {
                                    $set('hari', null);
                                }
                            })
                            ->helperText('Pilih tanggal jadwal. Hari akan terisi otomatis.')
                            ->rules(['nullable', 'date'])
                            ->validationMessages([
                                'date' => 'Format tanggal tidak valid.',
                            ]),

                        // ✅ Hari — readonly, terisi otomatis dari tanggal
                        Select::make('hari')
                            ->label('Hari')
                            ->options(collect(JadwalModel::HARI)->mapWithKeys(fn($h) => [$h => $h]))
                            ->required()
                            ->native(false)
                            ->disabled() // di-disable karena auto-fill dari tanggal
                            ->dehydrated() // tetap kirim nilainya saat submit meski disabled
                            ->helperText('Terisi otomatis dari tanggal yang dipilih.')
                            ->rules(['required', 'in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu'])
                            ->validationMessages([
                                'required' => 'Hari wajib dipilih (pilih tanggal terlebih dahulu).',
                                'in'       => 'Hari tidak valid.',
                            ]),

                    ])->columns(2),

                Section::make('Slot Waktu')
                    ->description('Atur jam mulai, jam selesai, dan urutan jadwal.')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        TimePicker::make('jam_mulai')
                            ->label('Jam Mulai')
                            ->required()
                            ->seconds(false)
                            ->live()
                            ->rules(['required', 'date_format:H:i'])
                            ->validationMessages([
                                'required'    => 'Jam mulai wajib diisi.',
                                'date_format' => 'Format jam tidak valid.',
                            ]),

                        TimePicker::make('jam_selesai')
                            ->label('Jam Selesai')
                            ->required()
                            ->seconds(false)
                            ->rules([
                                'required',
                                'date_format:H:i',
                                fn(Get $get) => 'after:' . ($get('jam_mulai') ?? '07:00'),
                            ])
                            ->validationMessages([
                                'required'    => 'Jam selesai wajib diisi.',
                                'date_format' => 'Format jam tidak valid.',
                                'after'       => 'Jam selesai harus setelah jam mulai.',
                            ]),

                        TextInput::make('urutan')
                            ->label('Urutan')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(255)
                            ->helperText('Urutan tampil jadwal dalam satu hari (0 = pertama).')
                            ->rules(['nullable', 'integer', 'min:0', 'max:255'])
                            ->validationMessages([
                                'integer' => 'Urutan harus berupa angka.',
                                'min'     => 'Urutan minimal 0.',
                                'max'     => 'Urutan maksimal 255.',
                            ]),

                        TextInput::make('ruang')
                            ->label('Ruang')
                            ->placeholder('Contoh: Ruang 12, Lab Komputer')
                            ->maxLength(255)
                            ->helperText('Opsional. Kosongkan jika tidak ada ruang khusus.')
                            ->rules(['nullable', 'string', 'max:255'])
                            ->validationMessages([
                                'max' => 'Ruang maksimal 255 karakter.',
                            ]),
                    ])->columns(2),

                Section::make('Mata Pelajaran & Guru')
                    ->description('Kosongkan jika slot ini adalah waktu istirahat.')
                    ->icon('heroicon-o-book-open')
                    ->schema([
                        Toggle::make('is_break')
                            ->label('Slot Istirahat')
                            ->helperText('Aktifkan jika slot ini adalah waktu istirahat/sholat.')
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function (bool $state, Set $set) {
                                if ($state) {
                                    $set('mata_pelajaran_id', null);
                                    $set('guru_id', null);
                                }
                            })
                            ->columnSpanFull(),

                        Select::make('mata_pelajaran_id')
                            ->label('Mata Pelajaran')
                            ->relationship('mataPelajaran', 'nama')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->hidden(fn(Get $get) => $get('is_break'))
                            ->disabled(
                                static fn(): bool => !MataPelajaran::query()->exists()
                            )
                            ->helperText(
                                static fn(): string => MataPelajaran::query()->exists()
                                    ? 'Pilih mata pelajaran untuk slot ini.'
                                    : '⚠️ Belum ada data mata pelajaran. Tambahkan mata pelajaran terlebih dahulu.'
                            )
                            ->rules(['nullable', 'integer', 'exists:mata_pelajarans,id'])
                            ->validationMessages([
                                'exists' => 'Mata pelajaran tidak ditemukan.',
                            ]),

                        Select::make('guru_id')
                            ->label('Guru')
                            ->relationship('guru', 'nama_lengkap')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->hidden(fn(Get $get) => $get('is_break'))
                            ->disabled(
                                static fn(): bool => !GuruModel::query()->exists()
                            )
                            ->helperText(
                                static fn(): string => GuruModel::query()->exists()
                                    ? 'Pilih guru yang mengajar pada slot ini.'
                                    : '⚠️ Belum ada data guru. Tambahkan guru terlebih dahulu.'
                            )
                            ->rules(['nullable', 'integer', 'exists:gurus,id'])
                            ->validationMessages([
                                'exists' => 'Guru tidak ditemukan.',
                            ]),

                        TextInput::make('label')
                            ->label('Label')
                            ->placeholder('Contoh: Istirahat & Sholat Dzuhur')
                            ->maxLength(255)
                            ->helperText('Opsional. Label khusus untuk slot istirahat.')
                            ->visible(fn(Get $get) => $get('is_break'))
                            ->rules(['nullable', 'string', 'max:255'])
                            ->validationMessages([
                                'max' => 'Label maksimal 255 karakter.',
                            ]),
                    ])->columnSpanFull(),
            ]);
    }
}
