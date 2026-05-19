<?php

namespace App\Filament\Resources\Angkatans\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AngkatanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // ─── Section 1: Informasi Angkatan ───────────────────────
                Section::make('Informasi Angkatan')
                    ->description('Isi data identitas angkatan dengan lengkap dan benar.')
                    ->icon('heroicon-o-academic-cap')
                    ->collapsible()
                    ->schema([

                        TextInput::make('nama')
                            ->label('Nama Angkatan')
                            ->placeholder('Contoh: Angkatan 2026')
                            ->prefixIcon('heroicon-o-tag')
                            ->required()
                            ->minLength(3)
                            ->maxLength(100)
                            ->unique(
                                table: 'angkatans',
                                column: 'nama',
                                ignoreRecord: true,
                            )
                            ->rules(['regex:/^[a-zA-Z0-9\s\-\.]+$/'])
                            ->validationMessages([
                                'required'  => 'Nama angkatan wajib diisi.',
                                'min'       => 'Nama angkatan minimal 3 karakter.',
                                'max'       => 'Nama angkatan maksimal 100 karakter.',
                                'unique'    => 'Nama angkatan sudah terdaftar.',
                                'regex'     => 'Nama angkatan hanya boleh mengandung huruf, angka, spasi, tanda hubung, dan titik.',
                            ])
                            ->helperText('Gunakan nama yang unik dan mudah dikenali.'),

                    ]),

                // ─── Section 2: Periode Angkatan ─────────────────────────
                Section::make('Periode Angkatan')
                    ->description('Tentukan rentang tahun masuk dan tahun lulus angkatan.')
                    ->icon('heroicon-o-calendar-days')
                    ->collapsible()
                    ->columns(2)
                    ->schema([

                        TextInput::make('tahun_masuk')
                            ->label('Tahun Masuk')
                            ->placeholder('Contoh: 2026')
                            ->prefixIcon('heroicon-o-arrow-right-circle')
                            ->numeric()
                            ->required()
                            ->minValue(2000)
                            ->maxValue(now()->year + 1)
                            ->rules([
                                'digits:4',
                                'integer',
                                'min:2000',
                                'max:' . (now()->year + 1),
                            ])
                            ->validationMessages([
                                'required'  => 'Tahun masuk wajib diisi.',
                                'digits'    => 'Tahun masuk harus 4 digit.',
                                'integer'   => 'Tahun masuk harus berupa angka bulat.',
                                'min'       => 'Tahun masuk tidak boleh sebelum tahun 2000.',
                                'max'       => 'Tahun masuk tidak boleh melebihi tahun ' . (now()->year + 1) . '.',
                            ])
                            ->helperText('Tahun saat angkatan pertama masuk.'),

                        TextInput::make('tahun_lulus')
                            ->label('Tahun Lulus')
                            ->placeholder('Contoh: 2030')
                            ->prefixIcon('heroicon-o-arrow-left-circle')
                            ->numeric()
                            ->nullable()
                            ->minValue(2000)
                            ->maxValue(now()->year + 10)
                            ->gte('tahun_masuk')
                            ->rules([
                                'nullable',
                                'digits:4',
                                'integer',
                                'min:2000',
                                'max:' . (now()->year + 10),
                            ])
                            ->validationMessages([
                                'digits'    => 'Tahun lulus harus 4 digit.',
                                'integer'   => 'Tahun lulus harus berupa angka bulat.',
                                'min'       => 'Tahun lulus tidak boleh sebelum tahun 2000.',
                                'max'       => 'Tahun lulus tidak boleh melebihi tahun ' . (now()->year + 10) . '.',
                                'gte'       => 'Tahun lulus tidak boleh lebih awal dari tahun masuk.',
                            ])
                            ->helperText('Kosongkan jika angkatan belum lulus.'),

                    ]),

                // ─── Section 3: Status & Konfigurasi ─────────────────────
                Section::make('Status & Konfigurasi')
                    ->description('Atur status aktif angkatan ini dalam sistem.')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsible()
                    ->schema([

                        Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->onIcon('heroicon-m-check-circle')
                            ->offIcon('heroicon-m-x-circle')
                            ->onColor('success')
                            ->offColor('danger')
                            ->helperText('Aktifkan jika angkatan ini masih digunakan dalam sistem.'),

                    ]),

            ]);
    }
}
