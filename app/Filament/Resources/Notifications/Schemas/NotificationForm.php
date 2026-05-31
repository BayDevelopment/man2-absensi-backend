<?php

namespace App\Filament\Resources\Notifications\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class NotificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pengguna')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Select::make('user_id')
                            ->label('Pengguna')
                            ->relationship(
                                name: 'user',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn(Builder $query) => $query->where('role', 'siswa')
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->unique(
                                table: 'user_notification_settings',
                                column: 'user_id',
                                ignoreRecord: true,
                            )
                            ->validationMessages([
                                'unique' => 'Siswa ini sudah memiliki pengaturan notifikasi.',
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Pengaturan Notifikasi')
                    ->icon('heroicon-o-bell-alert')
                    ->description('Aktifkan atau nonaktifkan jenis notifikasi yang diterima pengguna.')
                    ->columns(2)
                    ->schema([

                        Toggle::make('kehadiran')
                            ->label('Notifikasi Kehadiran')
                            ->helperText('Notifikasi saat ada update absensi.')
                            ->onIcon('heroicon-m-check')
                            ->offIcon('heroicon-m-x-mark')
                            ->onColor('success')
                            ->default(true),

                        Toggle::make('pengumuman')
                            ->label('Notifikasi Pengumuman')
                            ->helperText('Notifikasi saat ada pengumuman baru.')
                            ->onIcon('heroicon-m-check')
                            ->offIcon('heroicon-m-x-mark')
                            ->onColor('success')
                            ->default(true),

                        Toggle::make('jadwal')
                            ->label('Notifikasi Jadwal')
                            ->helperText('Notifikasi perubahan atau pengingat jadwal.')
                            ->onIcon('heroicon-m-check')
                            ->offIcon('heroicon-m-x-mark')
                            ->onColor('success')
                            ->default(false),

                    ]),
            ]);
    }
}
