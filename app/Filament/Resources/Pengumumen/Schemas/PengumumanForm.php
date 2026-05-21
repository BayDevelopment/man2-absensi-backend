<?php

namespace App\Filament\Resources\Pengumumen\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class PengumumanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pengumuman')
                    ->description('Judul dan isi pengumuman yang akan ditampilkan.')
                    ->icon('heroicon-o-megaphone')
                    ->columnSpanFull()
                    ->schema([

                        TextInput::make('judul')
                            ->label('Judul')
                            ->placeholder('Masukkan judul pengumuman…')
                            ->required()
                            ->minLength(5)
                            ->maxLength(255)
                            ->validationMessages([
                                'required'  => 'Judul pengumuman wajib diisi.',
                                'min'       => 'Judul minimal :min karakter.',
                                'max'       => 'Judul maksimal :max karakter.',
                            ])
                            ->columnSpanFull(),

                        RichEditor::make('isi')
                            ->label('Isi Pengumuman')
                            ->placeholder('Tulis isi pengumuman di sini…')
                            ->required()
                            ->minLength(10)
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'bulletList',
                                'orderedList',
                                'blockquote',
                                'link',
                                'h2',
                                'h3',
                                'undo',
                                'redo',
                            ])
                            ->validationMessages([
                                'required' => 'Isi pengumuman wajib diisi.',
                                'min'      => 'Isi pengumuman minimal :min karakter.',
                            ])
                            ->columnSpanFull(),

                    ]),

                Section::make('Jadwal & Status')
                    ->description('Atur kapan pengumuman mulai dan selesai ditayangkan.')
                    ->icon('heroicon-o-calendar-days')
                    ->columnSpanFull()
                    ->schema([

                        DateTimePicker::make('published_at')
                            ->label('Tanggal Publish')
                            ->helperText('Kosongkan agar langsung tayang saat disimpan, atau klik "Sekarang" untuk auto-isi.')
                            ->nullable()
                            ->native(false)
                            ->withoutSeconds()
                            ->displayFormat('d M Y, H:i')
                            ->closeOnDateSelection()
                            ->suffixAction(
                                Action::make('sekarang')
                                    ->label('Sekarang')
                                    ->icon('heroicon-o-clock')
                                    ->action(function (Set $set) {
                                        $set('published_at', now()->format('Y-m-d H:i:s'));
                                    })
                            ),

                        DateTimePicker::make('expired_at')
                            ->label('Tanggal Kedaluwarsa')
                            ->helperText('Kosongkan jika tidak ada batas waktu.')
                            ->nullable()
                            ->native(false)
                            ->withoutSeconds()
                            ->displayFormat('d M Y, H:i')
                            ->closeOnDateSelection()
                            ->after('published_at')
                            ->validationMessages([
                                'after' => 'Tanggal kedaluwarsa harus setelah tanggal publish.',
                            ]),

                        Toggle::make('is_active')
                            ->label('Aktifkan Pengumuman')
                            ->helperText('Nonaktifkan untuk menyembunyikan pengumuman sementara.')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger')
                            ->columnSpanFull(),

                    ]),

                Hidden::make('dibuat_oleh')
                    ->default(fn() => Auth::id()),

            ]);
    }
}
