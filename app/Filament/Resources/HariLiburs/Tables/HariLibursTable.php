<?php

namespace App\Filament\Resources\HariLiburs\Tables;

use App\Models\HariLiburModel;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HariLibursTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(fn(HariLiburModel $record): ?string => $record->keterangan),

                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'nasional' => 'Libur Nasional',
                        'sekolah' => 'Libur Sekolah',
                        'ujian' => 'Ujian',
                        'semester' => 'Semester',
                        'cuti_bersama' => 'Cuti Bersama',
                        'kegiatan_sekolah' => 'Kegiatan Sekolah',
                        'rapat_guru' => 'Rapat Guru',
                        'bencana' => 'Bencana',
                        'lainnya' => 'Lainnya',
                        default => ucfirst($state),
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'nasional' => 'danger',
                        'sekolah' => 'warning',
                        'ujian' => 'info',
                        'semester' => 'success',
                        'cuti_bersama' => 'primary',
                        'kegiatan_sekolah' => 'gray',
                        'rapat_guru' => 'purple',
                        'bencana' => 'danger',
                        'lainnya' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('tanggal_mulai')
                    ->label('Tanggal Mulai')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('tanggal_selesai')
                    ->label('Tanggal Selesai')
                    ->date('d M Y')
                    ->placeholder('1 Hari')
                    ->sortable(),

                TextColumn::make('periode')
                    ->label('Periode')
                    ->getStateUsing(function (HariLiburModel $record): string {
                        if (! $record->tanggal_selesai) {
                            return $record->tanggal_mulai->translatedFormat('d M Y');
                        }

                        return $record->tanggal_mulai->translatedFormat('d M Y')
                            . ' - '
                            . $record->tanggal_selesai->translatedFormat('d M Y');
                    }),

                IconColumn::make('is_libur')
                    ->label('Libur')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('warning'),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('dibuatOleh.name')
                    ->label('Dibuat Oleh')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('jenis')
                    ->label('Jenis')
                    ->options([
                        'nasional' => 'Libur Nasional',
                        'sekolah' => 'Libur Sekolah',
                        'ujian' => 'Ujian',
                        'semester' => 'Semester',
                        'cuti_bersama' => 'Cuti Bersama',
                        'kegiatan_sekolah' => 'Kegiatan Sekolah',
                        'rapat_guru' => 'Rapat Guru',
                        'bencana' => 'Bencana',
                        'lainnya' => 'Lainnya',
                    ])
                    ->native(false),

                TernaryFilter::make('is_libur')
                    ->label('Status Libur')
                    ->placeholder('Semua Data')
                    ->trueLabel('Libur')
                    ->falseLabel('Bukan Libur / Kegiatan Khusus'),

                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),

                Filter::make('rentang_tanggal')
                    ->label('Rentang Tanggal')
                    ->form([
                        DatePicker::make('tanggal_dari')
                            ->label('Dari Tanggal')
                            ->native(false),

                        DatePicker::make('tanggal_sampai')
                            ->label('Sampai Tanggal')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['tanggal_dari'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal_mulai', '>=', $date),
                            )
                            ->when(
                                $data['tanggal_sampai'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal_mulai', '<=', $date),
                            );
                    }),
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
