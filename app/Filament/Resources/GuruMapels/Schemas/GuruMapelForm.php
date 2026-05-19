<?php

namespace App\Filament\Resources\GuruMapels\Schemas;

use App\Models\GuruModel;
use App\Models\MataPelajaran;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class GuruMapelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Penugasan')
                ->description('Tentukan guru dan mata pelajaran yang akan dihubungkan.')
                ->icon('heroicon-o-academic-cap')
                ->columnSpanFull()
                ->schema([
                    Grid::make(2)->schema([

                        Select::make('guru_id')
                            ->label('Guru')
                            ->relationship('guru', 'nama_lengkap')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->live()
                            ->disabled(static fn(): bool => ! GuruModel::query()->exists())
                            ->dehydrated(true)   // ← nilai tetap terkirim meski disabled
                            ->helperText(
                                static fn(): ?string => GuruModel::query()->exists()
                                    ? null
                                    : '⚠️ Belum ada data guru. Silakan tambahkan guru terlebih dahulu.'
                            )
                            ->validationAttribute('guru')
                            ->rules(['required', 'integer'])   // ← hapus exists, biar Filament yang handle
                            ->validationMessages([
                                'required' => 'Guru wajib dipilih.',
                                'integer'  => 'Pilihan guru tidak valid.',
                            ]),

                        Select::make('mata_pelajaran_id')
                            ->label('Mata Pelajaran')
                            ->relationship('mataPelajaran', 'nama')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->live()
                            ->disabled(static fn(): bool => ! MataPelajaran::query()->exists())
                            ->helperText(
                                static fn(): ?string => MataPelajaran::query()->exists()
                                    ? null
                                    : '⚠️ Belum ada mata pelajaran. Silakan tambahkan mata pelajaran terlebih dahulu.'
                            )
                            ->validationAttribute('mata pelajaran')
                            ->rules([
                                'required',
                                'integer',
                                'exists:mata_pelajarans,id',
                                static fn(Get $get, ?Model $record): Unique =>
                                Rule::unique('guru_mata_pelajaran', 'mata_pelajaran_id')
                                    ->where('guru_id', (int) $get('guru_id'))
                                    ->ignore($record?->id),
                            ])
                            ->validationMessages([
                                'required' => 'Mata pelajaran wajib dipilih.',
                                'integer'  => 'Pilihan mata pelajaran tidak valid.',
                                'exists'   => 'Mata pelajaran yang dipilih tidak ditemukan.',
                                'unique'   => 'Guru ini sudah memiliki mata pelajaran tersebut.',
                            ]),

                    ]),

                    Toggle::make('is_active')
                        ->label('Status Aktif')
                        ->helperText('Nonaktifkan jika penugasan ini tidak berlaku lagi.')
                        ->default(true)
                        ->required(),
                ]),
        ]);
    }
}
