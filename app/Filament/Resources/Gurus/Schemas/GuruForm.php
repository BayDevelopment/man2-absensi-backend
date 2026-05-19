<?php

namespace App\Filament\Resources\Gurus\Schemas;

use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class GuruForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Akun')
                    ->description('Data akun pengguna yang terhubung dengan guru.')
                    ->icon('heroicon-o-user-circle')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('user_id')
                            ->label('Akun Pengguna')
                            ->relationship(
                                name: 'user',
                                titleAttribute: 'name',
                                modifyQueryUsing: static fn($query) => $query
                                    ->where('role', 'guru')
                                    ->whereNotNull('email_verified_at')
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->live() // ← ganti reactive() dengan live() untuk Filament v3
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $userId = $get('user_id');
                                $email = User::find($userId)?->email;
                                $set('email', $email ?? '');
                            })
                            ->disabled(
                                static fn(): bool => ! User::query()
                                    ->where('role', 'guru')
                                    ->whereNotNull('email_verified_at')
                                    ->exists()
                            )
                            ->dehydrated(true)
                            ->helperText(
                                static fn(): string => User::query()
                                    ->where('role', 'guru')
                                    ->whereNotNull('email_verified_at')
                                    ->exists()
                                    ? 'Pilih akun login yang akan digunakan guru ini. Satu akun hanya bisa terhubung ke satu guru.'
                                    : '⚠️ Belum ada akun guru yang sudah verifikasi email. Minta guru untuk verifikasi email terlebih dahulu.'
                            )
                            ->columnSpanFull()
                            ->validationAttribute('akun pengguna')
                            ->rules(['required', 'integer'])
                            ->validationMessages([
                                'required' => 'Akun pengguna wajib dipilih.',
                                'integer'  => 'Pilihan akun pengguna tidak valid.',
                            ]),
                    ]),

                Section::make('Data Pribadi')
                    ->description('Informasi pribadi guru.')
                    ->icon('heroicon-o-identification')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('nip')
                            ->label('NIP')
                            ->placeholder('Contoh: 198504152010011023')
                            ->maxLength(30)
                            ->unique(ignoreRecord: true)
                            ->helperText('Nomor Induk Pegawai. Kosongkan jika belum memiliki NIP.')
                            ->rules(['nullable', 'string', 'max:30'])
                            ->validationMessages([
                                'unique' => 'NIP ini sudah digunakan oleh guru lain.',
                                'max'    => 'NIP maksimal 30 karakter.',
                            ]),

                        TextInput::make('nama_lengkap')
                            ->label('Nama Lengkap')
                            ->placeholder('Contoh: Budi Santoso, S.Pd.')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Isi dengan nama lengkap beserta gelar (jika ada).')
                            ->rules(['required', 'string', 'max:255'])
                            ->validationMessages([
                                'required' => 'Nama lengkap wajib diisi.',
                                'max'      => 'Nama lengkap maksimal 255 karakter.',
                            ]),

                        Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options([
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                            ])
                            ->native(false)
                            ->helperText('Pilih jenis kelamin guru.')
                            ->validationMessages([
                                'in' => 'Pilihan jenis kelamin tidak valid.',
                            ]),

                        Select::make('jabatan_id')
                            ->label('Jabatan')
                            ->relationship('jabatan', 'nama')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->native(false)
                            ->helperText('Jabatan struktural guru di sekolah. Kosongkan jika tidak memiliki jabatan.'),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->placeholder('Otomatis terisi saat akun dipilih...')
                            ->maxLength(255)
                            ->helperText('Email diambil otomatis dari akun pengguna yang dipilih.')
                            ->afterStateHydrated(
                                fn($component, $record) =>
                                $component->state($record?->user?->email)
                            )
                            ->disabled()
                            ->dehydrated(true),

                        TextInput::make('no_hp')
                            ->label('No. HP / WhatsApp')
                            ->tel()
                            ->placeholder('Contoh: 08123456789')
                            ->maxLength(20)
                            ->helperText('Nomor HP aktif yang bisa dihubungi, diawali 08 atau +62.')
                            ->rules([
                                'nullable',
                                'string',
                                'max:20',
                                'regex:/^(\+62|08)[0-9]{7,15}$/',
                                fn(Get $get, ?Model $record) => Rule::unique('gurus', 'no_hp')
                                    ->ignore($record?->id),
                            ])
                            ->validationMessages([
                                'max'    => 'Nomor HP maksimal 20 karakter.',
                                'regex'  => 'Format nomor HP tidak valid. Gunakan format 08xxxxxxxxx atau +62xxxxxxxxx.',
                                'unique' => 'Nomor HP sudah digunakan oleh guru lain.',
                            ]),

                        Textarea::make('alamat')
                            ->label('Alamat Lengkap')
                            ->placeholder('Contoh: Jl. Mawar No. 5, RT 02/RW 03, Kel. Sukamaju, Kec. Ciputat, Tangerang Selatan')
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('Isi dengan alamat tempat tinggal guru saat ini secara lengkap.')
                            ->rules(['nullable', 'string', 'max:500'])
                            ->validationMessages([
                                'max' => 'Alamat maksimal 500 karakter.',
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Foto Profil')
                    ->description('Unggah foto profil guru untuk ditampilkan di sistem.')
                    ->icon('heroicon-o-photo')
                    ->columnSpanFull()
                    ->schema([
                        FileUpload::make('foto')
                            ->label('Foto Profil')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1:1',
                                '4:3',
                            ])
                            ->directory('guru/foto')
                            ->disk('public')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('Unggah foto formal guru. Format yang diterima: JPG, PNG, WEBP. Ukuran maksimal 2MB. Rasio foto disarankan 1:1 (persegi).')
                            ->validationMessages([
                                'max_size'       => 'Ukuran foto tidak boleh lebih dari 2MB.',
                                'mimes'          => 'Format foto tidak valid. Gunakan JPG, PNG, atau WEBP.',
                                'image'          => 'File yang diunggah harus berupa gambar.',
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Data Wajah (Face Recognition)')
                    ->description('Data wajah digunakan untuk absensi otomatis berbasis pengenalan wajah.')
                    ->icon('heroicon-o-face-smile')
                    ->collapsed()
                    ->columnSpanFull()
                    ->schema([
                        FileUpload::make('face_image')
                            ->label('Foto Wajah')
                            ->image()
                            ->directory('guru/face')
                            ->disk('public')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png'])
                            ->helperText('Unggah foto wajah yang jelas, tampak depan, pencahayaan baik, tanpa kacamata dan masker. Format: JPG atau PNG. Maks. 2MB.')
                            ->rules(['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'])
                            ->validationMessages([
                                'max_size' => 'Ukuran foto wajah tidak boleh lebih dari 2MB.',
                                'mimes'    => 'Foto wajah hanya menerima format JPG atau PNG.',
                                'image'    => 'File yang diunggah harus berupa gambar.',
                            ])
                            ->columnSpanFull(),

                        Toggle::make('is_face_registered')
                            ->label('Wajah Sudah Terdaftar')
                            ->helperText('Aktifkan hanya jika data wajah guru sudah diproses dan terdaftar di sistem. Jangan aktifkan secara manual tanpa proses registrasi.')
                            ->inline(false),
                    ]),

                Section::make('Status Guru')
                    ->description('Atur status keaktifan guru di sistem.')
                    ->icon('heroicon-o-check-badge')
                    ->columnSpanFull()
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Guru Aktif')
                            ->helperText('Nonaktifkan jika guru sudah tidak mengajar (pensiun, resign, dll.). Guru yang nonaktif tidak dapat login dan tidak muncul di daftar absensi.')
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }
}
