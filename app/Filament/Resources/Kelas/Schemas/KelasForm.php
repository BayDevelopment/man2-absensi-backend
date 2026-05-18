<?php

namespace App\Filament\Resources\Kelas\Schemas;

use App\Models\GuruModel;
use App\Models\KelasModel;
use App\Models\TahunAjaranModel;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class KelasForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Kelas')
                    ->description('Lengkapi data kelas dengan benar.')
                    ->icon('heroicon-o-academic-cap')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('nama_kelas')
                                    ->label('Nama Kelas')
                                    ->placeholder('Contoh: X IPA 1')
                                    ->required()
                                    ->maxLength(50)
                                    ->regex('/^[A-Za-z0-9\s\-\/.]+$/')
                                    ->unique(
                                        table: KelasModel::class,
                                        column: 'nama_kelas',
                                        ignoreRecord: true,
                                        modifyRuleUsing: fn(Unique $rule, Get $get): Unique =>
                                        $rule->where('tahun_ajaran_id', $get('tahun_ajaran_id'))
                                    )
                                    ->validationMessages([
                                        'required' => 'Nama kelas wajib diisi.',
                                        'max'      => 'Nama kelas maksimal 50 karakter.',
                                        'regex'    => 'Nama kelas hanya boleh berisi huruf, angka, spasi, titik, garis miring, dan strip.',
                                        'unique'   => 'Nama kelas sudah digunakan pada tahun ajaran tersebut.',
                                    ]),

                                Select::make('tingkat')
                                    ->label('Tingkat')
                                    ->options([
                                        'X'   => 'X',
                                        'XI'  => 'XI',
                                        'XII' => 'XII',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->validationMessages([
                                        'required' => 'Tingkat wajib dipilih.',
                                    ]),

                                Select::make('jurusan')
                                    ->label('Jurusan / Program')
                                    ->options([
                                        'Tahfiz'         => '📖 Tahfiz',
                                        'Olimpiade'      => '🔬 Olimpiade',
                                        'Olahraga & Seni' => '⚽ Olahraga & Seni',
                                        'Multimedia'     => '💻 Multimedia',
                                        'Linguistik'     => '🌐 Linguistik',
                                    ])
                                    ->native(false)
                                    ->searchable()
                                    ->nullable(),

                                Select::make('wali_kelas_id')
                                    ->label('Wali Kelas')
                                    ->relationship(
                                        name: 'waliKelas',
                                        titleAttribute: 'nama_lengkap',
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->native(false)
                                    ->disabled(fn() => !GuruModel::query()->exists())    // ✅ lazy evaluated
                                    ->helperText(
                                        fn() => !GuruModel::query()->exists()
                                            ? '⚠️ Belum ada data guru. Tambahkan guru terlebih dahulu.'
                                            : null
                                    )
                                    ->validationMessages([
                                        'exists' => 'Wali kelas tidak valid.',
                                    ]),

                                Select::make('tahun_ajaran_id')
                                    ->label('Tahun Ajaran')
                                    ->relationship(
                                        name: 'tahunAjaran',
                                        titleAttribute: 'nama',
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->native(false)
                                    ->disabled(fn() => !TahunAjaranModel::query()->exists())    // ✅ lazy evaluated
                                    ->helperText(
                                        fn() => !TahunAjaranModel::query()->exists()
                                            ? '⚠️ Belum ada data tahun ajaran. Tambahkan tahun ajaran terlebih dahulu.'
                                            : null
                                    )
                                    ->validationMessages([
                                        'exists' => 'Tahun ajaran tidak valid.',
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
