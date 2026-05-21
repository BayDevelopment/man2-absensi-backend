<?php

namespace App\Filament\Resources\Pengaturans\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class PengaturansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')
                    ->label('Logo')
                    ->circular()
                    ->checkFileExistence(false)
                    ->getStateUsing(
                        fn($record) => $record?->logo
                            ? Storage::disk('public')->url($record->logo)
                            : asset('images/default-img.png')
                    ),

                TextColumn::make('nama_sekolah')
                    ->label('Nama Sekolah')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('kepala_sekolah')
                    ->label('Kepala Sekolah')
                    ->placeholder('Belum diisi')
                    ->searchable(),

                TextColumn::make('alamat')
                    ->label('Alamat')
                    ->placeholder('Belum diisi')
                    ->limit(40)
                    ->tooltip(fn($record) => $record->alamat)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->since()
                    ->tooltip(fn($record) => $record->updated_at->translatedFormat('d F Y, H:i')),
            ])
            ->filters([

                // Satu filter untuk cek kelengkapan semua field opsional
                Filter::make('kelengkapan_data')
                    ->label('Kelengkapan Data')
                    ->columnSpanFull()
                    ->form([
                        Select::make('field')
                            ->label('Tampilkan data yang...')
                            ->options([
                                'lengkap'        => '✅ Semua field sudah lengkap',
                                'tanpa_logo'     => '⚠️ Belum ada logo',
                                'tanpa_alamat'   => '⚠️ Belum ada alamat',
                                'tanpa_kepsek'   => '⚠️ Belum ada kepala sekolah',
                            ])
                            ->placeholder('Semua data'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return match ($data['field'] ?? null) {
                            'lengkap'      => $query
                                ->whereNotNull('logo')->where('logo', '!=', '')
                                ->whereNotNull('alamat')->where('alamat', '!=', '')
                                ->whereNotNull('kepala_sekolah')->where('kepala_sekolah', '!=', ''),
                            'tanpa_logo'   => $query->where(fn($q) => $q->whereNull('logo')->orWhere('logo', '')),
                            'tanpa_alamat' => $query->where(fn($q) => $q->whereNull('alamat')->orWhere('alamat', '')),
                            'tanpa_kepsek' => $query->where(fn($q) => $q->whereNull('kepala_sekolah')->orWhere('kepala_sekolah', '')),
                            default        => $query,
                        };
                    })
                    ->indicateUsing(function (array $data): ?string {
                        return match ($data['field'] ?? null) {
                            'lengkap'      => 'Kelengkapan: Semua field lengkap',
                            'tanpa_logo'   => 'Kelengkapan: Belum ada logo',
                            'tanpa_alamat' => 'Kelengkapan: Belum ada alamat',
                            'tanpa_kepsek' => 'Kelengkapan: Belum ada kepala sekolah',
                            default        => null,
                        };
                    }),

                // Filter rentang tanggal update
                Filter::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->columnSpanFull()
                    ->form([
                        DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal')
                            ->displayFormat('d/m/Y')
                            ->maxDate(now()),

                        DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal')
                            ->displayFormat('d/m/Y')
                            ->maxDate(now())
                            ->minDate(fn(Get $get) => $get('dari_tanggal')), // tidak boleh sebelum dari_tanggal
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['dari_tanggal'] ?? null,
                                fn($q, $date) => $q->whereDate('updated_at', '>=', $date)
                            )
                            ->when(
                                $data['sampai_tanggal'] ?? null,
                                fn($q, $date) => $q->whereDate('updated_at', '<=', $date)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['dari_tanggal'] ?? null) {
                            $indicators[] = 'Dari: ' . Carbon::parse($data['dari_tanggal'])->translatedFormat('d F Y');
                        }

                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators[] = 'Sampai: ' . Carbon::parse($data['sampai_tanggal'])->translatedFormat('d F Y');
                        }

                        return $indicators;
                    }),

            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),

                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Hapus data?')
                        ->modalDescription('Data akan dipindahkan ke trash.')
                        ->successNotification(
                            Notification::make()
                                ->title('Berhasil')
                                ->body('Data berhasil dihapus')
                                ->success()
                        ),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->button()
                    ->outlined(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
