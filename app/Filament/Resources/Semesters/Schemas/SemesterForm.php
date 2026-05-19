<?php

namespace App\Filament\Resources\Semesters\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class SemesterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Semester')
                    ->description('Atur periode semester berdasarkan tahun ajaran.')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Select::make('tahun_ajaran_id')
                            ->label('Tahun Ajaran')
                            ->relationship('tahunAjaran', 'nama')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->validationAttribute('tahun ajaran')
                            ->rules(['required', 'integer', 'exists:tahun_ajarans,id'])
                            ->validationMessages([
                                'required' => 'Tahun ajaran wajib dipilih.',
                                'exists'   => 'Tahun ajaran tidak ditemukan.',
                            ]),

                        Select::make('nama')
                            ->label('Nama Semester')
                            ->options([
                                'ganjil' => 'Ganjil',
                                'genap'  => 'Genap',
                            ])
                            ->required()
                            ->native(false)
                            ->live()
                            ->rules([
                                'required',
                                'in:ganjil,genap',
                                fn(Get $get, ?Model $record) => Rule::unique('semesters', 'nama')
                                    ->where('tahun_ajaran_id', $get('tahun_ajaran_id'))
                                    ->ignore($record?->id),
                            ])
                            ->validationMessages([
                                'required' => 'Nama semester wajib dipilih.',
                                'in'       => 'Nama semester tidak valid.',
                                'unique'   => 'Semester ini sudah ada di tahun ajaran tersebut.',
                            ]),

                        DatePicker::make('tanggal_mulai')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->native(false)
                            ->displayFormat('d F Y')
                            ->live()
                            ->rules(['required', 'date'])
                            ->validationMessages([
                                'required' => 'Tanggal mulai wajib diisi.',
                                'date'     => 'Format tanggal tidak valid.',
                            ]),

                        DatePicker::make('tanggal_selesai')
                            ->label('Tanggal Selesai')
                            ->required()
                            ->native(false)
                            ->displayFormat('d F Y')
                            ->rules([
                                'required',
                                'date',
                                fn(Get $get) => 'after:' . ($get('tanggal_mulai') ?? 'today'),
                            ])
                            ->validationMessages([
                                'required' => 'Tanggal selesai wajib diisi.',
                                'date'     => 'Format tanggal tidak valid.',
                                'after'    => 'Tanggal selesai harus setelah tanggal mulai.',
                            ]),

                        Toggle::make('is_active')
                            ->label('Aktifkan Semester Ini')
                            ->helperText('Hanya satu semester yang bisa aktif dalam satu waktu. Mengaktifkan ini akan menonaktifkan semester lain.')
                            ->default(false)
                            ->rules(['boolean']),
                    ])->columnSpanFull(),
            ]);
    }
}
