<?php

namespace App\Filament\Resources\SecurityUsers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SecurityUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pengguna')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Select::make('user_id')
                            ->label('Siswa')
                            ->relationship(
                                name: 'user',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn(Builder $query) => $query->where('role', 'siswa')
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ]),

                Section::make('Pengaturan Keamanan')
                    ->icon('heroicon-o-lock-closed')
                    ->description('Kelola keamanan akun siswa.')
                    ->columns(2)
                    ->schema([

                        Toggle::make('two_factor')
                            ->label('Two Factor Authentication')
                            ->helperText('Verifikasi dua langkah saat login.')
                            ->onIcon('heroicon-m-shield-check')
                            ->offIcon('heroicon-m-shield-exclamation')
                            ->onColor('success')
                            ->default(false),

                        Toggle::make('notif_login')
                            ->label('Notifikasi Login')
                            ->helperText('Kirim notifikasi setiap kali akun login.')
                            ->onIcon('heroicon-m-check')
                            ->offIcon('heroicon-m-x-mark')
                            ->onColor('success')
                            ->default(true),

                        Toggle::make('logout_otomatis')
                            ->label('Logout Otomatis')
                            ->helperText('Otomatis logout saat sesi tidak aktif.')
                            ->onIcon('heroicon-m-check')
                            ->offIcon('heroicon-m-x-mark')
                            ->onColor('warning')
                            ->default(false),

                    ]),
            ]);
    }
}
