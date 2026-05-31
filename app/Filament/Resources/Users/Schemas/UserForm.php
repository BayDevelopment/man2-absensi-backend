<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data User')->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->label('Username')
                            ->required()
                            ->minLength(3)
                            ->maxLength(50)
                            ->regex('/^[A-Za-z0-9]+$/')
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn(?string $state) => trim($state))
                            ->validationMessages([
                                'required' => 'Username wajib diisi.',
                                'min' => 'Username minimal 3 karakter.',
                                'max' => 'Username maksimal 50 karakter.',
                                'regex' => 'Username hanya boleh berisi huruf dan angka tanpa spasi atau simbol.',
                                'unique' => 'Username sudah digunakan.',
                            ])
                            ->helperText('Username hanya boleh berisi huruf besar, huruf kecil, dan angka tanpa spasi.'),

                        Select::make('role')
                            ->label('Role')
                            ->options([
                                'admin' => 'Admin',
                                'guru'  => 'Guru',
                                'siswa' => 'Siswa',
                            ])
                            ->required()
                            ->default('siswa')
                            ->native(false)
                            ->rules([
                                Rule::in(['admin', 'guru', 'siswa']),
                            ])
                            ->live()
                            ->validationMessages([
                                'required' => 'Role wajib dipilih.',
                                'in' => 'Role tidak valid.',
                            ])
                            ->helperText('Pilih role user.'),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn(?string $state) => $state ? strtolower(trim($state)) : null)
                            ->validationMessages([
                                'email' => 'Format email tidak valid.',
                                'unique' => 'Email sudah digunakan.',
                                'max' => 'Email maksimal 255 karakter.',
                            ])
                            ->helperText('Akan menerima email untuk verifikasi.'),

                        TextInput::make('nisn')
                            ->label('NISN')
                            ->required(fn(Get $get) => $get('role') === 'siswa')
                            ->numeric()
                            ->length(10)
                            ->unique(ignoreRecord: true)
                            ->visible(fn(Get $get) => $get('role') === 'siswa')
                            ->dehydrateStateUsing(fn(?string $state) => $state ? trim($state) : null)
                            ->validationMessages([
                                'required' => 'NISN wajib diisi untuk role siswa.',
                                'numeric' => 'NISN hanya boleh berisi angka.',
                                'size' => 'NISN harus tepat 10 digit.',
                                'unique' => 'NISN sudah digunakan.',
                            ])
                            ->helperText('Hanya untuk siswa, wajib 10 digit angka.'),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->required(fn(string $operation) => $operation === 'create')
                            ->rules([
                                Password::min(8)
                                    ->letters()
                                    ->mixedCase()
                                    ->numbers()
                                    ->symbols()
                                    ->uncompromised(),
                            ])
                            ->confirmed()
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn(?string $state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn(?string $state) => filled($state))
                            ->validationMessages([
                                'required' => 'Password wajib diisi saat membuat user.',
                                'confirmed' => 'Konfirmasi password tidak sama.',
                            ])
                            ->helperText('Minimal 8 karakter, berisi huruf besar, huruf kecil, angka, simbol, dan tidak termasuk password yang bocor.'),

                        TextInput::make('password_confirmation')
                            ->label('Konfirmasi Password')
                            ->password()
                            ->revealable()
                            ->required(fn(string $operation) => $operation === 'create')
                            ->dehydrated(false)
                            ->helperText('Ulangi password yang sama.'),
                    ]),

                    Placeholder::make('email_verified_at')
                        ->label('Status Email')
                        ->content(fn($record) => $record?->email_verified_at
                            ? '✅ Terverifikasi pada ' . $record->email_verified_at->format('d M Y H:i')
                            : '❌ Belum terverifikasi')
                        ->visibleOn('edit'),
                ])
                    ->columnSpanFull(),
            ]);
    }
}
