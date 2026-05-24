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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

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

                // ✅ Ganti DatePicker → TextColumn
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('hari')
                    ->label('Hari')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'Senin'  => 'info',
                        'Selasa' => 'success',
                        'Rabu'   => 'warning',
                        'Kamis'  => 'danger',
                        'Jumat'  => 'primary',
                        'Sabtu'  => 'gray',
                        default  => 'gray',
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
                    ->placeholder('-'),

                TextColumn::make('guru.nama_lengkap')
                    ->label('Guru')
                    ->sortable()
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('ruang')
                    ->label('Ruang')
                    ->placeholder('-')
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
            ->defaultSort('tanggal', 'asc')
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

                Filter::make('tanggal')
                    ->label('Rentang Tanggal')
                    ->form([
                        DatePicker::make('dari')
                            ->label('Dari Tanggal')
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        DatePicker::make('sampai')
                            ->label('Sampai Tanggal')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari'],
                                fn(Builder $q, $date) => $q->whereDate('tanggal', '>=', $date)
                            )
                            ->when(
                                $data['sampai'],
                                fn(Builder $q, $date) => $q->whereDate('tanggal', '<=', $date)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['dari'] ?? null) {
                            $indicators['dari'] = 'Dari: ' . \Carbon\Carbon::parse($data['dari'])->format('d/m/Y');
                        }

                        if ($data['sampai'] ?? null) {
                            $indicators['sampai'] = 'Sampai: ' . \Carbon\Carbon::parse($data['sampai'])->format('d/m/Y');
                        }

                        return $indicators;
                    }),

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
