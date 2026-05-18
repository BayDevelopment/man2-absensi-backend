<?php

namespace App\Filament\Resources\TahunAjarans\Schemas;

use App\Models\TahunAjaranModel;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TahunAjaranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Tahun Ajaran')
                    ->description('Lengkapi informasi tahun ajaran dengan benar.')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Grid::make(2)
                            ->schema([

                                TextInput::make('nama')
                                    ->label('Nama Tahun Ajaran')
                                    ->placeholder('Contoh: 2025/2026')
                                    ->required()
                                    ->maxLength(20)
                                    ->regex('/^\d{4}\/\d{4}$/')
                                    ->unique(
                                        table: TahunAjaranModel::class,
                                        column: 'nama',
                                        ignoreRecord: true,
                                    )
                                    ->validationMessages([
                                        'required' => 'Nama tahun ajaran wajib diisi.',
                                        'max'      => 'Nama tahun ajaran maksimal 20 karakter.',
                                        'regex'    => 'Format harus seperti: 2025/2026.',
                                        'unique'   => 'Tahun ajaran ini sudah terdaftar.',
                                    ]),

                                // Spacer agar Toggle tidak menggantung di kolom kanan
                                Grid::make(1)
                                    ->columnSpan(1)
                                    ->schema([
                                        Toggle::make('is_active')
                                            ->label('Jadikan Tahun Ajaran Aktif')
                                            ->helperText('Hanya satu tahun ajaran yang bisa aktif dalam satu waktu.')
                                            ->default(false)
                                            ->inline(false),
                                    ]),

                                DatePicker::make('tanggal_mulai')
                                    ->label('Tanggal Mulai')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d M Y')
                                    ->closeOnDateSelection()
                                    ->maxDate(fn(Get $get) => $get('tanggal_selesai') ?? null)
                                    ->validationMessages([
                                        'required' => 'Tanggal mulai wajib diisi.',
                                        'before'   => 'Tanggal mulai harus sebelum tanggal selesai.',
                                    ]),

                                DatePicker::make('tanggal_selesai')
                                    ->label('Tanggal Selesai')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d M Y')
                                    ->closeOnDateSelection()
                                    ->minDate(fn(Get $get) => $get('tanggal_mulai') ?? null)
                                    ->after('tanggal_mulai')
                                    ->validationMessages([
                                        'required' => 'Tanggal selesai wajib diisi.',
                                        'after'    => 'Tanggal selesai harus setelah tanggal mulai.',
                                    ]),

                            ]),
                    ]),
            ]);
    }
}
