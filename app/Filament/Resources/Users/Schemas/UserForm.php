<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

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
                            ->maxLength(255)
                            ->regex('/^[A-Za-z0-9]+$/')
                            ->helperText('Username hanya boleh berisi huruf besar, huruf kecil, dan angka tanpa spasi'),

                        Select::make('role')
                            ->label('Role')
                            ->options([
                                'admin' => 'Admin',
                                'guru'  => 'Guru',
                                'siswa' => 'Siswa',
                            ])
                            ->required()
                            ->default('siswa')
                            ->helperText('Pilih role user'),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->nullable()
                            ->maxLength(255)
                            ->helperText('Email user, opsional'),

                        TextInput::make('nisn')
                            ->label('NISN')
                            ->unique(ignoreRecord: true)
                            ->nullable()
                            ->numeric()
                            ->maxLength(10)
                            ->helperText('Hanya untuk siswa, 10 digit angka'),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $operation) => $operation === 'create')
                            ->minLength(8)
                            ->maxLength(255)
                            ->helperText('Min 8 karakter, kosongkan jika tidak ingin mengubah password'),

                        TextInput::make('password_confirmation')
                            ->label('Konfirmasi Password')
                            ->password()
                            ->revealable()
                            ->same('password')
                            ->required(fn(string $operation) => $operation === 'create')
                            ->dehydrated(false)
                            ->helperText('Ulangi password yang sama'),
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
