<?php

namespace App\Filament\Resources\Appearances\Schemas;

use App\Models\AppearanceModel;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class AppearanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pengguna')
                    ->icon('heroicon-o-user')
                    ->columnSpanFull()
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
                            ->unique(
                                table: 'user_appearance_settings',
                                column: 'user_id',
                                ignoreRecord: true,
                            )
                            ->validationMessages([
                                'unique' => 'Siswa ini sudah memiliki pengaturan tampilan.',
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Pengaturan Tampilan')
                    ->icon('heroicon-o-swatch')
                    ->description('Sesuaikan tampilan aplikasi untuk pengguna ini.')
                    ->columnSpanFull()
                    ->schema([

                        Select::make('tema')
                            ->label('Tema')
                            ->options(AppearanceModel::TEMA)
                            ->default('light')
                            ->required()
                            ->in(array_keys(AppearanceModel::TEMA))
                            ->validationMessages([
                                'required' => 'Tema wajib dipilih.',
                                'in'       => 'Tema yang dipilih tidak valid.',
                            ])
                            ->native(false)
                            ->prefixIcon('heroicon-o-sun'),

                        Select::make('bahasa')
                            ->label('Bahasa')
                            ->options(AppearanceModel::BAHASA)
                            ->default('id')
                            ->required()
                            ->in(array_keys(AppearanceModel::BAHASA))
                            ->validationMessages([
                                'required' => 'Bahasa wajib dipilih.',
                                'in'       => 'Bahasa yang dipilih tidak valid.',
                            ])
                            ->native(false)
                            ->prefixIcon('heroicon-o-language'),

                        Select::make('ukuran_teks')
                            ->label('Ukuran Teks')
                            ->options(AppearanceModel::UKURAN_TEKS)
                            ->default('normal')
                            ->required()
                            ->in(array_keys(AppearanceModel::UKURAN_TEKS))
                            ->validationMessages([
                                'required' => 'Ukuran teks wajib dipilih.',
                                'in'       => 'Ukuran teks yang dipilih tidak valid.',
                            ])
                            ->native(false)
                            ->prefixIcon('heroicon-o-magnifying-glass'),

                    ]),
            ]);
    }
}
