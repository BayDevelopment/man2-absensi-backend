<?php

namespace App\Filament\Resources\JabatanGurus\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class JabatanGuruForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Jabatan')
                    ->description('Isi data jabatan dengan lengkap dan benar.')
                    ->icon('heroicon-o-identification')
                    ->schema([

                        TextInput::make('nama')
                            ->label('Nama Jabatan')
                            ->placeholder('Contoh: Kepala Sekolah, Wali Kelas, Guru Mapel...')
                            ->trim()
                            ->required()
                            ->minLength(3)
                            ->maxLength(100)
                            ->regex('/^[\pL\s\.\-]+$/u')
                            ->unique(
                                table: \App\Models\JabatanModel::class,
                                column: 'nama',
                                ignoreRecord: true,   // Filament V5 otomatis ignore record saat edit
                            )
                            ->validationMessages([
                                'regex'  => 'Nama jabatan hanya boleh berisi huruf, spasi, titik, dan tanda hubung.',
                                'unique' => 'Nama jabatan ini sudah terdaftar.',
                            ])
                            ->columnSpanFull(),

                        Toggle::make('bisa_mengajar')
                            ->label('Bisa Mengajar')
                            ->helperText('Aktifkan jika jabatan ini diizinkan untuk mengajar.')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger')
                            ->rules(['boolean'])
                            ->columnSpanFull(),

                    ])
                    ->columns(2),
            ]);
    }
}
