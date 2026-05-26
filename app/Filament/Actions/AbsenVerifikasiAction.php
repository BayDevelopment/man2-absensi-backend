<?php

namespace App\Filament\Actions;

use App\Models\SiswaModel;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class AbsenVerifikasiAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'absen_verifikasi';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $threshold = 0.42;

        $this
            ->label('Absen Masuk')
            ->icon('heroicon-o-camera')
            ->color('primary')
            ->modalHeading('Absen Masuk — Verifikasi Wajah')
            ->modalDescription('Pastikan hanya satu wajah siswa yang terlihat di kamera.')
            ->modalWidth('2xl')
            ->modalSubmitAction(false)           // nonaktifkan tombol submit default
            ->modalCancelActionLabel('Batal')

            // Tidak pakai form() — semua data lewat Alpine → Livewire dispatch
            ->modalContent(fn($record) => view('filament.actions.absen-verifikasi-modal', [
                'jadwal'    => $record,
                'siswaList' => $this->getSiswaFaceList(),
                'threshold' => $threshold,
            ]))

            // Tombol "Catat Kehadiran" di footer — trigger via Alpine event
            ->extraModalFooterActions([
                Action::make('catat_kehadiran')
                    ->label('Catat Kehadiran')
                    ->color('primary')
                    ->extraAttributes([
                        // Alpine akan set dataset ini sebelum klik
                        'x-on:click' => "
                            if (!window.__faceMatchResult) {
                                \$dispatch('notify', { type: 'danger', message: 'Verifikasi wajah belum selesai.' });
                                return;
                            }
                            \$wire.dispatch('catat-kehadiran', window.__faceMatchResult)
                        ",
                    ])
                    ->action(fn() => null), // aksi asli ditangani listener di bawah
            ])

            ->registerListeners([
                'catat-kehadiran' => [
                    function (AbsenVerifikasiAction $action, array $arguments) use ($threshold): void {
                        $siswaId    = $arguments['siswa_id']          ?? null;
                        $distance   = isset($arguments['match_distance'])   ? (float) $arguments['match_distance']  : null;
                        $confidence = isset($arguments['match_confidence'])  ? (int)   $arguments['match_confidence'] : null;

                        if (! $siswaId || $distance === null || $confidence === null) {
                            Notification::make()
                                ->title('Verifikasi belum selesai')
                                ->body('Silakan aktifkan kamera dan lakukan verifikasi wajah terlebih dahulu.')
                                ->danger()
                                ->send();

                            $action->halt();
                            return;
                        }

                        if ($distance > $threshold) {
                            Notification::make()
                                ->title('Wajah tidak valid')
                                ->body('Jarak kecocokan wajah melebihi batas keamanan.')
                                ->danger()
                                ->send();

                            $action->halt();
                            return;
                        }

                        $siswa = SiswaModel::query()->find($siswaId);

                        if (! $siswa) {
                            Notification::make()
                                ->title('Siswa tidak ditemukan')
                                ->body('Data siswa hasil verifikasi tidak ditemukan di database.')
                                ->danger()
                                ->send();

                            $action->halt();
                            return;
                        }

                        /*
                         |------------------------------------------------------------------
                         | SIMPAN ABSENSI DI SINI
                         |------------------------------------------------------------------
                         | \App\Models\AbsensiModel::updateOrCreate(
                         |     [
                         |         'jadwal_id' => $action->getRecord()->getKey(),
                         |         'siswa_id'  => $siswa->getKey(),
                         |         'tanggal'   => now()->toDateString(),
                         |     ],
                         |     [
                         |         'status'            => 'hadir',
                         |         'jam_masuk'         => now()->format('H:i:s'),
                         |         'metode_absensi'    => 'face_verification',
                         |         'match_distance'    => $distance,
                         |         'match_confidence'  => $confidence,
                         |         'verified_by'       => auth()->id(),
                         |     ]
                         | );
                         */

                        Notification::make()
                            ->title('Absensi berhasil diverifikasi')
                            ->body(($siswa->nama_lengkap ?? $siswa->nama ?? 'Siswa') . ' berhasil dikenali. Confidence: ' . $confidence . '%.')
                            ->success()
                            ->send();

                        $action->close();
                    },
                ],
            ]);
    }

    private function getSiswaFaceList(): array
    {
        return SiswaModel::query()
            ->whereNotNull('face_descriptor')
            ->get()
            ->map(function (SiswaModel $siswa): ?array {
                $descriptor = $siswa->face_descriptor;

                // Guard: bisa sudah array (dari model cast) atau string JSON
                if (is_string($descriptor)) {
                    $descriptor = json_decode($descriptor, true);
                }

                if (! is_array($descriptor) || count($descriptor) !== 128) {
                    return null;
                }

                return [
                    'id'              => $siswa->getKey(),
                    'nama'            => $siswa->nama ?? null,
                    'nama_lengkap'    => $siswa->nama_lengkap ?? $siswa->nama ?? 'Siswa',
                    'nis'             => $siswa->nis ?? null,
                    'face_descriptor' => array_values(array_map('floatval', $descriptor)),
                    'face_image_path' => $siswa->face_image_path ?? null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
