<?php

namespace App\Filament\Resources\Siswas\Schemas;

use App\Models\User;
use App\Models\Kelas;
use App\Models\KelasModel;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
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

                        TextInput::make('nisn')
                            ->label('NISN')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->numeric()
                            ->minLength(10)
                            ->maxLength(10)
                            ->helperText('Masukkan NISN siswa (10 digit angka).')
                            ->validationAttribute('NISN')
                            ->validationMessages([
                                'required'  => 'NISN wajib diisi.',
                                'numeric'   => 'NISN hanya boleh berisi angka.',
                                'min'       => 'NISN harus tepat 10 digit.',
                                'max'       => 'NISN harus tepat 10 digit.',
                                'unique'    => 'NISN ini sudah terdaftar.',
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

                    Select::make('user_id')
                        ->label('Akun User')
                        ->relationship(
                            name: 'user',
                            titleAttribute: 'name',
                            modifyQueryUsing: static fn($query) => $query
                                ->where('role', 'siswa')
                                ->whereNotNull('email_verified_at')  // harus sudah verified
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->native(false)
                        ->disabled(
                            static fn(): bool => ! User::query()
                                ->where('role', 'siswa')
                                ->whereNotNull('email_verified_at')
                                ->exists()
                        )
                        ->dehydrated(true)
                        ->helperText(
                            static fn(): string => User::query()
                                ->where('role', 'siswa')
                                ->whereNotNull('email_verified_at')
                                ->exists()
                                ? 'Pilih akun user dengan role siswa yang terkait.'
                                : '⚠️ Belum ada akun siswa yang sudah verifikasi email. Minta siswa untuk verifikasi email terlebih dahulu.'
                        )
                        ->validationAttribute('akun user')
                        ->rules(['required', 'integer'])
                        ->validationMessages([
                            'required' => 'Akun user wajib dipilih.',
                            'integer'  => 'Pilihan akun user tidak valid.',
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
