<?php

namespace App\Filament\Resources\HariLiburs\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class HariLiburForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Hari Libur')
                    ->description('Atur tanggal libur, kegiatan khusus sekolah, ujian, atau pengecualian jadwal absensi.')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama Hari Libur / Kegiatan')
                            ->placeholder('Contoh: Libur Akhir Semester Genap')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        DatePicker::make('tanggal_mulai')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->native(false)
                            ->live()
                            ->helperText('Tanggal awal hari libur atau kegiatan khusus.'),

                        DatePicker::make('tanggal_selesai')
                            ->label('Tanggal Selesai')
                            ->native(false)
                            ->nullable()
                            ->minDate(fn(Get $get) => $get('tanggal_mulai'))
                            ->rule('after_or_equal:tanggal_mulai')
                            ->helperText('Kosongkan jika hanya berlaku satu hari.'),

                        Select::make('jenis')
                            ->label('Jenis')
                            ->options([
                                'nasional' => 'Libur Nasional',
                                'sekolah' => 'Libur Sekolah',
                                'ujian' => 'Ujian',
                                'semester' => 'Libur Semester',
                                'cuti_bersama' => 'Cuti Bersama',
                                'kegiatan_sekolah' => 'Kegiatan Sekolah',
                                'rapat_guru' => 'Rapat Guru',
                                'bencana' => 'Bencana',
                                'lainnya' => 'Lainnya',
                            ])
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                if ($state !== 'lainnya') {
                                    $set('text_lainnya', null);
                                }
                            })
                            ->helperText('Pilih kategori hari libur atau kegiatan khusus.'),

                        Textarea::make('text_lainnya')
                            ->label('Keterangan Jenis Lainnya')
                            ->placeholder('Contoh: Libur khusus berdasarkan keputusan kepala sekolah.')
                            ->required(fn(Get $get): bool => $get('jenis') === 'lainnya')
                            ->visible(fn(Get $get): bool => $get('jenis') === 'lainnya')
                            ->rows(3)
                            ->columnSpanFull(),

                        Toggle::make('is_libur')
                            ->label('Apakah Libur?')
                            ->default(true)
                            ->live()
                            ->helperText(
                                fn(Get $get): string => $get('is_libur')
                                    ? 'Siswa tidak perlu melakukan absensi pada tanggal ini.'
                                    : 'Siswa tetap melakukan absensi, tetapi hari ini dianggap sebagai kegiatan khusus.'
                            ),

                        Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->helperText('Nonaktifkan jika data ini belum ingin digunakan oleh sistem.'),

                        Textarea::make('keterangan')
                            ->label('Keterangan Tambahan')
                            ->placeholder('Tambahkan catatan jika diperlukan.')
                            ->rows(4)
                            ->columnSpanFull(),

                        Hidden::make('dibuat_oleh')
                            ->default(fn() => Auth::id()),
                    ]),
            ]);
    }
}
