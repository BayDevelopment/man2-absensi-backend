<?php

namespace App\Filament\Resources\Absensis\Tables;

use App\Filament\Resources\Absensis\AbsensiResource;
use App\Filament\Resources\Absensis\Schemas\AbsensiForm;
use App\Models\AbsensiModel;
use App\Models\JamSekolahModel;
use Carbon\Carbon;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class AbsensisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('siswa.nama_lengkap')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('jadwal.mataPelajaran.nama')
                    ->label('Mata Pelajaran')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('jam_masuk')
                    ->label('Jam Masuk')
                    ->formatStateUsing(fn($state) => $state ? substr($state, 0, 5) : '—')
                    ->toggleable(),

                TextColumn::make('jam_keluar')
                    ->label('Jam Keluar')
                    ->formatStateUsing(fn($state) => $state ? substr($state, 0, 5) : '—')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'hadir'          => 'success',
                        'terlambat'      => 'warning',
                        'izin'           => 'info',
                        'sakit', 'alfa'  => 'danger',
                        default          => 'gray',
                    })
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
                        return $query->when($data['periode'] ?? null, function ($q, $periode) {
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
                        if (empty($data['periode'])) return null;
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
                                $data['dari_tanggal'] ?? null,
                                fn($q, $v) => $q->whereDate('tanggal', '>=', $v)
                            )
                            ->when(
                                $data['sampai_tanggal'] ?? null,
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

            ->headerActions([
                Action::make('absen_massal')
                    ->label('Absen Massal')
                    ->icon('heroicon-o-user-group')
                    ->color('primary')
                    ->form(AbsensiForm::absenMassal())
                    ->action(fn(array $data) => static::prosesAbsenMassal($data))
                    ->modalHeading('Absen Massal Siswa')
                    ->modalSubmitActionLabel('Simpan Absensi')
                    ->modalWidth('5xl'),

                Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('danger')
                    ->action(function ($livewire) {
                        $records = $livewire->getFilteredTableQuery()->with([
                            'siswa',
                            'kelas',
                            'jadwal.mataPelajaran',
                            'dicatatOleh',
                        ])->get();

                        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
                            'exports.absensi-pdf',
                            compact('records')
                        )->setPaper('a4', 'landscape');

                        return Response::streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, 'absensi-' . Carbon::now()->format('Y-m-d') . '.pdf');
                    }),

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

                        return \Maatwebsite\Excel\Facades\Excel::download(
                            new \App\Exports\AbsensiExport($records),
                            'absensi-' . Carbon::now()->format('Y-m-d') . '.xlsx'
                        );
                    }),
            ])

            ->recordActions([
                ActionGroup::make([

                    Action::make('lihat_detail')
                        ->label('Lihat Detail')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalHeading(
                            fn(AbsensiModel $record): string =>
                            'Detail Absensi — ' . ($record->siswa?->nama_lengkap ?? 'Siswa')
                        )
                        ->modalContent(fn(AbsensiModel $record) => view(
                            'filament.modals.absensi-detail',
                            ['record' => $record]
                        ))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup')
                        ->modalWidth('lg'),

                    EditAction::make()
                        ->visible(function ($record): bool {
                            if (Auth::user()->hasRole('admin')) return true;
                            return Carbon::parse($record->tanggal)
                                ->startOfDay()
                                ->gte(Carbon::yesterday()->startOfDay());
                        })
                        ->tooltip(function ($record): string {
                            if (Auth::user()->hasRole('admin')) return 'Edit data absensi';
                            return Carbon::parse($record->tanggal)
                                ->startOfDay()
                                ->gte(Carbon::yesterday()->startOfDay())
                                ? 'Edit data absensi'
                                : 'Tidak dapat diedit — melewati batas H+1';
                        }),

                    DeleteAction::make()
                        ->visible(fn(): bool => Auth::user()->hasRole('admin'))
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
                ])
                    ->label('Aksi')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->button()
                    ->outlined(),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn(): bool => Auth::user()->hasRole('admin'))
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

    // =========================================================================
    // PROSES ABSEN MASSAL
    // =========================================================================
    protected static function prosesAbsenMassal(array $data): void
    {
        $kelasId         = $data['kelas_id'];
        $jadwalId        = $data['jadwal_id'];
        $tanggal         = $data['tanggal'];
        $jamMasukDefault = $data['jam_masuk_default'] ?? Carbon::now()->format('H:i');
        $siswaAbsensi    = $data['siswa_absensi'] ?? [];

        if (!$jadwalId || !$kelasId || !$tanggal) {
            Notification::make()->title('Data tidak lengkap.')->danger()->send();
            return;
        }

        $jamKeluarOtomatis = AbsensiResource::getJamKeluarOtomatis(
            $jadwalId,
            $kelasId,
            $tanggal
        );

        $jamSekolah = JamSekolahModel::where('aktif', 1)->first();
        $batasTerlambatLabel = $jamSekolah?->batas_terlambat
            ? substr($jamSekolah->batas_terlambat, 0, 5)
            : null;

        $berhasil = 0;
        $dilewati = 0;

        DB::transaction(function () use (
            $siswaAbsensi,
            $jadwalId,
            $kelasId,
            $tanggal,
            $jamMasukDefault,
            $jamKeluarOtomatis,
            $batasTerlambatLabel,
            &$berhasil,
            &$dilewati
        ) {
            foreach ($siswaAbsensi as $item) {
                $siswaId  = $item['siswa_id'];
                $status   = $item['status'] ?? 'hadir';
                $jamMasuk = $item['jam_masuk'] ?? null;

                if (in_array($status, ['hadir', 'terlambat']) && !$jamMasuk) {
                    $jamMasuk = $jamMasukDefault;
                }

                $jamKeluar = in_array($status, ['hadir', 'terlambat'])
                    ? $jamKeluarOtomatis
                    : null;

                $keterangan = trim($item['keterangan'] ?? '');
                if ($keterangan === '') {
                    $keterangan = match ($status) {
                        'terlambat' => 'Absen terlambat (dicatat guru/admin'
                            . ($batasTerlambatLabel
                                ? ', batas ' . $batasTerlambatLabel
                                . ', masuk ' . substr($jamMasuk ?? '', 0, 5)
                                : '')
                            . ')',
                        'izin'  => 'Tidak hadir dengan izin',
                        'sakit' => 'Tidak hadir karena sakit',
                        default => null,
                    };
                }

                $existing = AbsensiModel::where('siswa_id', $siswaId)
                    ->where('jadwal_id', $jadwalId)
                    ->whereDate('tanggal', $tanggal)
                    ->first();

                if ($existing?->verified_by_face && in_array($existing->status, ['hadir', 'terlambat'])) {
                    $dilewati++;
                    continue;
                }

                AbsensiModel::updateOrCreate(
                    ['siswa_id' => $siswaId, 'jadwal_id' => $jadwalId, 'tanggal' => $tanggal],
                    [
                        'kelas_id'     => $kelasId,
                        'status'       => $status,
                        'jam_masuk'    => in_array($status, ['hadir', 'terlambat']) ? $jamMasuk : null,
                        'jam_keluar'   => $jamKeluar,
                        'keterangan'   => $keterangan,
                        'dicatat_oleh' => Auth::id(),
                    ]
                );

                $berhasil++;
            }
        });

        $pesan = "✅ Absensi berhasil disimpan untuk {$berhasil} siswa.";
        if ($dilewati > 0) {
            $pesan .= " {$dilewati} siswa dilewati (sudah absen via wajah).";
        }

        Notification::make()->title($pesan)->success()->send();
    }
}
