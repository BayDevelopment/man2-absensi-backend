<?php

namespace App\Filament\Resources\Mapels\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MapelForm
{
    public static function configure(Schema $schema): Schema
    {


        return $schema
            ->components([
                Section::make('Informasi Mata Pelajaran')
                    ->description('Masukkan data mata pelajaran dengan lengkap.')
                    ->icon('heroicon-o-book-open')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])
                            ->schema([
                                TextInput::make('nama')
                                    ->label('Nama Mata Pelajaran')
                                    ->prefixIcon('heroicon-o-book-open')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Contoh: Matematika')
                                    ->rules(['required', 'string', 'max:255']),

                                TextInput::make('kode')
                                    ->label('Kode Mapel')
                                    ->prefixIcon('heroicon-o-tag')
                                    ->nullable()
                                    ->maxLength(255)
                                    ->placeholder('Contoh: MTK')
                                    ->rules(['nullable', 'string', 'max:255']),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),
            ]);
    }
}
