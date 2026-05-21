<?php

namespace App\Filament\Resources\Pengaturans\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PengaturanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Sekolah')
                    ->description('Informasi umum sekolah yang akan ditampilkan di aplikasi, laporan, dan sertifikat.')
                    ->icon('heroicon-o-building-library')
                    ->schema([

                        TextInput::make('nama_sekolah')
                            ->label('Nama Sekolah')
                            ->required()
                            ->string()
                            ->minLength(3)
                            ->maxLength(255)
                            ->default('MAN 2 Kota Cilegon')
                            ->placeholder('Contoh: MAN 2 Kota Cilegon')
                            ->helperText('Nama resmi sekolah sesuai SK. Akan tampil di header laporan & absensi.')
                            ->columnSpanFull(),

                        FileUpload::make('logo')
                            ->label('Logo Sekolah')
                            ->image()
                            ->directory('logo-sekolah')
                            ->visibility('public')
                            ->disk('public')
                            ->imagePreviewHeight('120')
                            ->maxSize(2048)                  // 2MB dalam KB
                            ->minSize(5)                     // minimal 5KB, hindari file kosong/corrupt
                            ->acceptedFileTypes([
                                'image/png',
                                'image/jpeg',
                                'image/jpg',
                            ])
                            ->imageCropAspectRatio('1:1')    // crop kotak agar logo rapi
                            ->imageResizeTargetWidth('400')  // resize otomatis, hemat storage
                            ->imageResizeTargetHeight('400')
                            ->imageResizeMode('cover')
                            ->helperText('Format: PNG, JPG. Maks. 2MB. Rasio 1:1 (kotak) direkomendasikan agar tidak terpotong.')
                            ->nullable()
                            ->columnSpanFull(),

                        Textarea::make('alamat')
                            ->label('Alamat Sekolah')
                            ->rows(3)
                            ->minLength(10)
                            ->maxLength(500)
                            ->placeholder('Jl. Contoh No. 1, Kelurahan, Kecamatan, Kota, Kode Pos')
                            ->helperText('Alamat lengkap sekolah. Akan ditampilkan di surat dan laporan resmi.')
                            ->nullable()
                            ->columnSpanFull(),

                        TextInput::make('kepala_sekolah')
                            ->label('Nama Kepala Sekolah')
                            ->string()
                            ->minLength(3)
                            ->maxLength(255)
                            ->regex('/^[\pL\s\.\,\-]+$/u') // hanya huruf, spasi, titik, koma, strip
                            ->validationMessages([
                                'regex' => 'Nama hanya boleh mengandung huruf, spasi, titik, koma, dan tanda hubung.',
                            ])
                            ->placeholder('Contoh: Drs. Ahmad Fauzi, M.Pd.')
                            ->helperText('Nama lengkap beserta gelar akademik. Akan muncul di tanda tangan laporan.')
                            ->nullable(),

                    ])
                    ->columnSpanFull(),
            ]);
    }
}
