<?php

namespace App\Filament\Resources\Siswas\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SiswaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Siswa')->schema([
                    Grid::make(2)->schema([
                        TextInput::make('nisn')
                            ->label('NISN')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->numeric()
                            ->maxLength(10)
                            ->helperText('Masukkan NISN siswa (10 digit angka)'),

                        TextInput::make('nama_lengkap')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Masukkan nama lengkap sesuai Kartu Pelajar'),
                    ]),

                    Select::make('user_id')
                        ->label('User')
                        ->relationship('user', 'name') // ambil dari user table
                        ->required()
                        ->searchable()
                        ->helperText('Pilih akun user yang terkait dengan siswa'),

                    Select::make('jenis_kelamin')
                        ->label('Jenis Kelamin')
                        ->options([
                            'L' => 'Laki-laki',
                            'P' => 'Perempuan',
                        ])
                        ->required()
                        ->helperText('Pilih jenis kelamin siswa'),

                    TextInput::make('no_hp')
                        ->label('No. HP')
                        ->tel()
                        ->maxLength(15)
                        ->helperText('Masukkan nomor HP aktif, tanpa spasi atau simbol'),

                    Select::make('kelas_id')
                        ->label('Kelas')
                        ->relationship('kelas', 'nama_kelas')
                        ->nullable()
                        ->searchable()
                        ->helperText('Pilih kelas siswa, bisa dikosongkan jika belum ditempatkan'),

                    FileUpload::make('foto')
                        ->label('Foto Profil')
                        ->image()
                        ->directory('siswas/foto')
                        ->nullable()
                        ->maxSize(1024)
                        ->helperText('Unggah foto profil siswa, format: jpg/png, max 1MB'),

                    FileUpload::make('face_image')
                        ->label('Foto Face Reference')
                        ->image()
                        ->directory('siswas/face')
                        ->nullable()
                        ->maxSize(1024)
                        ->helperText('Foto untuk face recognition, opsional, max 1MB'),

                    Placeholder::make('face_descriptor')
                        ->label('Face Descriptor')
                        ->content(fn($record) => $record?->face_descriptor ? json_encode($record->face_descriptor) : 'Belum terdaftar')
                        ->helperText('Hanya untuk informasi teknis, tidak bisa diubah'),

                    Toggle::make('is_face_registered')
                        ->label('Face Terdaftar')
                        ->default(false)
                        ->helperText('Tandai jika face recognition sudah didaftarkan'),

                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true)
                        ->helperText('Matikan jika siswa tidak aktif lagi'),
                ]),
            ]);
    }
}
