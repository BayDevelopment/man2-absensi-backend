<?php

namespace App\Filament\Resources\Absensis\Tables;

use App\Exports\AbsensiExport;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Excel;

class AbsensisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('siswa.nama')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('jadwal.nama_pelajaran')
                    ->label('Mata Pelajaran')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('jam_masuk')
                    ->label('Jam Masuk')
                    ->time('H:i')
                    ->toggleable(),

                TextColumn::make('jam_keluar')
                    ->label('Jam Keluar')
                    ->time('H:i')
                    ->toggleable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'hadir',
                        'warning' => 'terlambat',
                        'info'    => 'izin',
                        'danger'  => fn($state) => in_array($state, ['sakit', 'alfa']),
                    ])
                    ->formatStateUsing(fn($state) => match ($state) {
                        'hadir'     => '✅ Hadir',
                        'terlambat' => '⏰ Terlambat',
                        'izin'      => '📝 Izin',
                        'sakit'     => '🏥 Sakit',
                        'alfa'      => '❌ Alfa',
                        default     => $state,
                    }),

                IconColumn::make('verified_by_face')
                    ->label('Face')
                    ->boolean()
                    ->trueIcon('heroicon-o-face-smile')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),

                TextColumn::make('dicatatOleh.name')
                    ->label('Dicatat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            // ── FILTERS ────────────────────────────────────────────────────────
            ->filters([

                Filter::make('periode')
                    ->form([
                        Select::make('periode')
                            ->label('Filter Periode')
                            ->options([
                                'hari_ini'    => '📅 Hari Ini',
                                'minggu_ini'  => '📆 Minggu Ini',
                                'minggu_lalu' => '📆 Minggu Lalu',
                                'bulan_ini'   => '🗓️ Bulan Ini',
                                'bulan_lalu'  => '🗓️ Bulan Lalu',
                            ])
                            ->native(false)
                            ->placeholder('Semua periode'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['periode'], function ($q, $periode) {
                            return match ($periode) {
                                'hari_ini'    => $q->whereDate('tanggal', Carbon::today()),
                                'minggu_ini'  => $q->whereBetween('tanggal', [
                                    Carbon::now()->startOfWeek(),
                                    Carbon::now()->endOfWeek(),
                                ]),
                                'minggu_lalu' => $q->whereBetween('tanggal', [
                                    Carbon::now()->subWeek()->startOfWeek(),
                                    Carbon::now()->subWeek()->endOfWeek(),
                                ]),
                                'bulan_ini'   => $q->whereMonth('tanggal', Carbon::now()->month)
                                    ->whereYear('tanggal', Carbon::now()->year),
                                'bulan_lalu'  => $q->whereMonth('tanggal', Carbon::now()->subMonth()->month)
                                    ->whereYear('tanggal', Carbon::now()->subMonth()->year),
                                default       => $q,
                            };
                        });
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['periode']) return null;
                        return 'Periode: ' . match ($data['periode']) {
                            'hari_ini'    => 'Hari Ini',
                            'minggu_ini'  => 'Minggu Ini',
                            'minggu_lalu' => 'Minggu Lalu',
                            'bulan_ini'   => 'Bulan Ini',
                            'bulan_lalu'  => 'Bulan Lalu',
                            default       => $data['periode'],
                        };
                    }),

                SelectFilter::make('kelas_id')
                    ->label('Kelas')
                    ->relationship('kelas', 'nama_kelas')
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('status')
                    ->label('Status Kehadiran')
                    ->options([
                        'hadir'     => '✅ Hadir',
                        'terlambat' => '⏰ Terlambat',
                        'izin'      => '📝 Izin',
                        'sakit'     => '🏥 Sakit',
                        'alfa'      => '❌ Alfa',
                    ])
                    ->native(false),

                Filter::make('tanggal_range')
                    ->form([
                        Grid::make(2)->schema([
                            DatePicker::make('dari_tanggal')
                                ->label('Dari Tanggal')
                                ->native(false)
                                ->displayFormat('d M Y'),
                            DatePicker::make('sampai_tanggal')
                                ->label('Sampai Tanggal')
                                ->native(false)
                                ->displayFormat('d M Y'),
                        ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn($q, $v) => $q->whereDate('tanggal', '>=', $v)
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn($q, $v) => $q->whereDate('tanggal', '<=', $v)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['dari_tanggal'] ?? null)
                            $indicators[] = 'Dari: ' . Carbon::parse($data['dari_tanggal'])->format('d M Y');
                        if ($data['sampai_tanggal'] ?? null)
                            $indicators[] = 'Sampai: ' . Carbon::parse($data['sampai_tanggal'])->format('d M Y');
                        return $indicators;
                    }),

            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->recordActions([
                ActionGroup::make([

                    // ── EDIT: hanya tampil jika H+0 atau H+1, atau user adalah admin ──
                    EditAction::make()
                        ->visible(function ($record): bool {
                            if (auth()->user()->hasRole('admin')) {
                                return true;
                            }

                            $tanggalAbsen = Carbon::parse($record->tanggal)->startOfDay();
                            $kemarin      = Carbon::yesterday()->startOfDay();

                            // Tampil hanya jika tanggal absen >= kemarin (H+0 atau H+1)
                            return $tanggalAbsen->gte($kemarin);
                        })
                        ->tooltip(function ($record): string {
                            if (auth()->user()->hasRole('admin')) {
                                return 'Edit data absensi';
                            }

                            $tanggalAbsen = Carbon::parse($record->tanggal)->startOfDay();
                            $kemarin      = Carbon::yesterday()->startOfDay();

                            return $tanggalAbsen->gte($kemarin)
                                ? 'Edit data absensi'
                                : 'Tidak dapat diedit — melewati batas H+1';
                        }),

                    // ── DELETE: hanya admin ──────────────────────────────────────────
                    DeleteAction::make()
                        ->visible(fn(): bool => auth()->user()->hasRole('admin'))
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Data Absensi?')
                        ->modalDescription('Data absensi akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.')
                        ->modalSubmitActionLabel('Ya, Hapus')
                        ->successNotification(
                            Notification::make()
                                ->title('Berhasil Dihapus')
                                ->body('Data absensi berhasil dihapus.')
                                ->success()
                        ),

                    // ── EXPORT PDF ───────────────────────────────────────────────────
                    Action::make('export_pdf')
                        ->label('Export PDF')
                        ->icon('heroicon-o-document-text')
                        ->color('danger')
                        ->action(function ($livewire) {
                            $records = $livewire->getFilteredTableQuery()->with([
                                'siswa',
                                'kelas',
                                'jadwal',
                                'dicatatOleh',
                            ])->get();

                            return response()->streamDownload(function () use ($records) {
                                echo view('exports.absensi-pdf', compact('records'))->render();
                            }, 'absensi-' . now()->format('Y-m-d') . '.pdf');
                        }),

                    // ── EXPORT EXCEL ─────────────────────────────────────────────────
                    Action::make('export_excel')
                        ->label('Export Excel')
                        ->icon('heroicon-o-table-cells')
                        ->color('success')
                        ->action(function ($livewire) {
                            $records = $livewire->getFilteredTableQuery()->with([
                                'siswa',
                                'kelas',
                                'jadwal',
                                'dicatatOleh',
                            ])->get();

                            return Excel::download(
                                new AbsensiExport($records),
                                'absensi-' . now()->format('Y-m-d') . '.xlsx'
                            );
                        }),

                ])
                    ->label('Aksi')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->button()
                    ->outlined(),
            ])
            ->toolbarActions([
                // ── BULK DELETE: hanya admin ─────────────────────────────────────
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn(): bool => auth()->user()->hasRole('admin'))
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Data Terpilih?')
                        ->modalDescription('Semua data absensi yang dipilih akan dihapus permanen.')
                        ->modalSubmitActionLabel('Ya, Hapus Semua')
                        ->successNotification(
                            Notification::make()
                                ->title('Berhasil Dihapus')
                                ->body('Data absensi terpilih berhasil dihapus.')
                                ->success()
                        ),
                ]),
            ]);
    }
}
