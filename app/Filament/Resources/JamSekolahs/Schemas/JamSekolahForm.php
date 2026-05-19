<?php

namespace App\Filament\Resources\JamSekolahs\Schemas;

use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class JamSekolahForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pengaturan Jam Sekolah')
                    ->description('Atur jam masuk dan batas toleransi keterlambatan siswa.')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        TimePicker::make('jam_masuk')
                            ->label('Jam Masuk')
                            ->required()
                            ->seconds(false)
                            ->default('07:00')
                            ->live()
                            ->helperText('Jam masuk resmi sekolah.')
                            ->rules(['required', 'date_format:H:i'])
                            ->validationMessages([
                                'required'    => 'Jam masuk wajib diisi.',
                                'date_format' => 'Format jam tidak valid.',
                            ]),

                        TimePicker::make('batas_terlambat')
                            ->label('Batas Terlambat')
                            ->required()
                            ->seconds(false)
                            ->default('07:15')
                            ->helperText('Siswa yang datang setelah jam ini dianggap terlambat.')
                            ->rules([
                                'required',
                                'date_format:H:i',
                                fn(Get $get) => 'after:' . ($get('jam_masuk') ?? '07:00'),
                            ])
                            ->validationMessages([
                                'required'    => 'Batas terlambat wajib diisi.',
                                'date_format' => 'Format jam tidak valid.',
                                'after'       => 'Batas terlambat harus setelah jam masuk.',
                            ]),

                        Toggle::make('aktif')
                            ->label('Aktifkan Jam Sekolah Ini')
                            ->helperText('Hanya satu jam sekolah yang aktif dalam satu waktu.')
                            ->default(true)
                            ->rules(['boolean']),
                    ])->columns(2),
            ]);
    }
}
