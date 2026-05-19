<?php

namespace App\Filament\Resources\Jadwals\Tables;

use App\Models\JadwalModel;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class JadwalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('hari')
                    ->label('Hari')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'Senin'  => 'info',
                        'Selasa' => 'success',
                        'Rabu'   => 'warning',
                        'Kamis'  => 'danger',
                        'Jumat'  => 'primary',
                        'Sabtu'  => 'gray',
                    }),

                TextColumn::make('jam_mulai')
                    ->label('Jam Mulai')
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('jam_selesai')
                    ->label('Jam Selesai')
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('mataPelajaran.nama')
                    ->label('Mata Pelajaran')
                    ->sortable()
                    ->searchable()
                    ->default('-'),

                TextColumn::make('guru.nama_lengkap')
                    ->label('Guru')
                    ->sortable()
                    ->searchable()
                    ->default('-'),

                TextColumn::make('ruang')
                    ->label('Ruang')
                    ->default('-')
                    ->searchable(),

                IconColumn::make('is_break')
                    ->label('Istirahat')
                    ->boolean()
                    ->trueIcon('heroicon-o-pause-circle')
                    ->falseIcon('heroicon-o-play-circle')
                    ->trueColor('warning')
                    ->falseColor('success'),

                TextColumn::make('urutan')
                    ->label('Urutan')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('kelas_id')
                    ->label('Kelas')
                    ->relationship('kelas', 'nama_kelas')
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('hari')
                    ->label('Hari')
                    ->options(collect(JadwalModel::HARI)->mapWithKeys(fn($h) => [$h => $h]))
                    ->native(false),

                SelectFilter::make('guru_id')
                    ->label('Guru')
                    ->relationship('guru', 'nama_lengkap')
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('tahun_ajaran_id')
                    ->label('Tahun Ajaran')
                    ->relationship('tahunAjaran', 'nama')
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('semester_id')
                    ->label('Semester')
                    ->relationship('semester', 'nama')
                    ->searchable()
                    ->preload()
                    ->native(false),

                TernaryFilter::make('is_break')
                    ->label('Slot Istirahat')
                    ->trueLabel('Istirahat')
                    ->falseLabel('Pelajaran')
                    ->native(false),
            ])
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
