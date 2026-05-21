<?php

namespace App\Filament\Resources\Siswas\Schemas;

use App\Models\User;
use App\Models\Kelas;
use App\Models\KelasModel;
use App\Models\SiswaModel;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class SiswaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Siswa')
                ->description('Lengkapi informasi data siswa dengan benar.')
                ->icon('heroicon-o-user')
                ->schema([

                    Grid::make(2)->schema([

                        Select::make('user_id')
                            ->label('Pilih Siswa')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->options(function (?SiswaModel $record) {
                                // 1. Ambil user_id yang sudah ada di tabel siswas
                                $userSudahTerdaftar = SiswaModel::query()
                                    ->whereNotNull('user_id')
                                    ->when(
                                        $record?->user_id,
                                        fn($query) => $query->where('user_id', '!=', $record->user_id)
                                    )
                                    ->pluck('user_id')
                                    ->toArray();

                                return User::query()
                                    // 2. Filter berdasarkan role siswa (kolom text atau Spatie)
                                    ->where(function ($query) {
                                        $query->where('role', 'siswa')
                                            ->orWhereHas('roles', function ($q) {
                                                $q->where('name', 'siswa');
                                            });
                                    })
                                    // 3. Pastikan user belum terdaftar di tabel siswas
                                    ->whereNotIn('id', $userSudahTerdaftar)
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($user) {
                                        // Dropdown mengambil dari kolom 'nisn' milik tabel users untuk info pencarian
                                        $nisnTampil = $user->nisn ?? 'Tanpa NISN';
                                        return [
                                            $user->id => "{$nisnTampil} - {$user->name}",
                                        ];
                                    })
                                    ->toArray();
                            })
                            ->afterStateHydrated(function (Set $set, ?SiswaModel $record) {
                                if (! $record?->user) {
                                    return;
                                }
                                // Saat edit, ambil data 'nis' dari tabel siswa atau fallback ke 'nisn' milik user
                                $set('nis', $record->nis ?? $record->user->nisn);
                                $set('email', $record->user->email);
                                $set('nama_lengkap', $record->nama_lengkap ?? $record->user->name);
                            })
                            ->afterStateUpdated(function (Set $set, ?int $state) {
                                if (! $state) {
                                    $set('nis', null);
                                    $set('email', null);
                                    $set('nama_lengkap', null);
                                    return;
                                }

                                $user = User::query()->find($state);

                                // Lempar data 'nisn' milik tabel USERS ke komponen form bernama 'nis'
                                $set('nis', $user?->nisn);
                                $set('email', $user?->email);
                                $set('nama_lengkap', $user?->name);
                            })
                            ->helperText('Pilih akun siswa yang belum memiliki profil di tabel siswa.')
                            ->validationAttribute('siswa')
                            ->validationMessages([
                                'required' => 'Siswa wajib dipilih.',
                            ]),

                        TextInput::make('nis') // 💡 Menggunakan nama field 'nis' sesuai kolom tabel siswas
                            ->label('NISN')
                            ->required()
                            ->disabled()
                            ->dehydrated() // Tetap disimpan ke database saat disubmit
                            ->numeric()
                            ->minLength(10)
                            ->maxLength(10)
                            ->unique(
                                table: SiswaModel::class,
                                column: 'nis', // 💡 Diubah ke 'nis' karena di tabel siswas kolomnya bernama 'nis'
                                ignoreRecord: true,
                            )
                            ->helperText('NISN otomatis diambil dari data user.')
                            ->validationAttribute('NISN')
                            ->validationMessages([
                                'required' => 'NISN wajib diisi.',
                                'numeric'  => 'NISN hanya boleh berisi angka.',
                                'min'      => 'NISN harus tepat 10 digit.',
                                'max'      => 'NISN harus tepat 10 digit.',
                                'unique'   => 'NISN ini sudah terdaftar.',
                            ]),

                        TextInput::make('email')
                            ->label('Email')
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->email()
                            ->helperText('Email otomatis diambil dari user yang sudah terverifikasi.')
                            ->validationAttribute('email')
                            ->validationMessages([
                                'required' => 'Email wajib diisi.',
                                'email'    => 'Format email tidak valid.',
                            ]),



                        TextInput::make('nama_lengkap')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->regex('/^[\pL\s\-\.]+$/u')          // hanya huruf, spasi, titik, strip
                            ->helperText('Masukkan nama lengkap sesuai Kartu Pelajar.')
                            ->validationAttribute('nama lengkap')
                            ->validationMessages([
                                'required' => 'Nama lengkap wajib diisi.',
                                'max'      => 'Nama lengkap maksimal 255 karakter.',
                                'regex'    => 'Nama lengkap hanya boleh berisi huruf, spasi, titik, atau strip.',
                            ]),

                    ]),
                    Select::make('jenis_kelamin')
                        ->label('Jenis Kelamin')
                        ->options([
                            'L' => 'Laki-laki',
                            'P' => 'Perempuan',
                        ])
                        ->required()
                        ->native(false)
                        ->helperText('Pilih jenis kelamin siswa.')
                        ->validationAttribute('jenis kelamin')
                        ->rules(['required', 'in:L,P'])
                        ->validationMessages([
                            'required' => 'Jenis kelamin wajib dipilih.',
                            'in'       => 'Pilihan jenis kelamin tidak valid.',
                        ]),

                    TextInput::make('no_hp')
                        ->label('No. HP')
                        ->tel()
                        ->maxLength(15)
                        ->nullable()
                        ->regex('/^(\+62|08)[0-9]{7,12}$/')      // format Indonesia
                        ->helperText('Format: 08xxxxxxxx atau +62xxxxxxxx, tanpa spasi.')
                        ->validationAttribute('nomor HP')
                        ->validationMessages([
                            'max'   => 'Nomor HP maksimal 15 karakter.',
                            'regex' => 'Format nomor HP tidak valid. Gunakan format 08xx atau +62xx.',
                        ]),

                    Select::make('kelas_id')
                        ->label('Kelas')
                        ->relationship('kelas', 'nama_kelas')
                        ->searchable()
                        ->nullable()
                        ->disabled(static fn(): bool => ! KelasModel::query()->exists())
                        ->helperText(
                            static fn(): string => KelasModel::query()->exists()
                                ? 'Pilih kelas siswa. Bisa dikosongkan jika belum ditempatkan.'
                                : '⚠️ Belum ada data kelas. Tambahkan kelas terlebih dahulu.'
                        )
                        ->validationAttribute('kelas')
                        ->rules(['nullable', 'integer', 'exists:kelas,id'])
                        ->validationMessages([
                            'integer' => 'Pilihan kelas tidak valid.',
                            'exists'  => 'Kelas yang dipilih tidak ditemukan.',
                        ]),

                    FileUpload::make('foto')
                        ->label('Foto Profil')
                        ->image()
                        ->directory('siswas/foto')
                        ->nullable()
                        ->maxSize(1024)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->helperText('Format: jpg/png/webp, maksimal 1MB.')
                        ->validationAttribute('foto profil')
                        ->validationMessages([
                            'max'   => 'Ukuran foto profil maksimal 1MB.',
                            'image' => 'File harus berupa gambar.',
                            'mimes' => 'Format foto harus jpg, png, atau webp.',
                        ]),

                    FileUpload::make('face_image')
                        ->label('Foto Face Reference')
                        ->image()
                        ->directory('siswas/face')
                        ->nullable()
                        ->maxSize(1024)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->helperText('Foto untuk face recognition. Format: jpg/png/webp, maksimal 1MB.')
                        ->validationAttribute('foto face reference')
                        ->validationMessages([
                            'max'   => 'Ukuran foto face reference maksimal 1MB.',
                            'image' => 'File harus berupa gambar.',
                            'mimes' => 'Format foto harus jpg, png, atau webp.',
                        ]),

                    Placeholder::make('face_descriptor')
                        ->label('Face Descriptor')
                        ->content(
                            static fn($record): string => $record?->face_descriptor
                                ? json_encode($record->face_descriptor)
                                : 'Belum terdaftar'
                        )
                        ->helperText('Informasi teknis face recognition, tidak bisa diubah secara manual.'),

                    Grid::make(2)->schema([

                        Toggle::make('is_face_registered')
                            ->label('Face Terdaftar')
                            ->default(false)
                            ->helperText('Tandai jika face recognition sudah didaftarkan.'),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Nonaktifkan jika siswa sudah tidak aktif.'),

                    ]),

                ])->columnSpanFull(),
        ]);
    }
}
