<?php

namespace App\Filament\Actions;

use App\Models\SiswaModel;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
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
            ->modalSubmitActionLabel('Catat Kehadiran')
            ->modalCancelActionLabel('Batal')

            ->form([
                Hidden::make('siswa_id')
                    ->dehydrated()
                    ->required(),

                Hidden::make('match_distance')
                    ->dehydrated()
                    ->required(),

                Hidden::make('match_confidence')
                    ->dehydrated()
                    ->required(),
            ])

            ->modalContent(fn($record) => view('filament.actions.absen-verifikasi-modal', [
                'jadwal' => $record,
                'siswaList' => $this->getSiswaFaceList(),
                'threshold' => $threshold,
            ]))

            ->action(function (array $data, $record) use ($threshold): void {
                $siswaId = $data['siswa_id'] ?? null;
                $distance = isset($data['match_distance']) ? (float) $data['match_distance'] : null;
                $confidence = isset($data['match_confidence']) ? (int) $data['match_confidence'] : null;

                if (! $siswaId || $distance === null || $confidence === null) {
                    Notification::make()
                        ->title('Verifikasi belum selesai')
                        ->body('Silakan aktifkan kamera dan lakukan verifikasi wajah terlebih dahulu.')
                        ->danger()
                        ->send();

                    $this->halt();
                    return;
                }

                if ($distance > $threshold) {
                    Notification::make()
                        ->title('Wajah tidak valid')
                        ->body('Jarak kecocokan wajah melebihi batas keamanan.')
                        ->danger()
                        ->send();

                    $this->halt();
                    return;
                }

                $siswa = SiswaModel::query()->find($siswaId);

                if (! $siswa) {
                    Notification::make()
                        ->title('Siswa tidak ditemukan')
                        ->body('Data siswa hasil verifikasi tidak ditemukan di database.')
                        ->danger()
                        ->send();

                    $this->halt();
                    return;
                }

                /*
                 |--------------------------------------------------------------------------
                 | SIMPAN ABSENSI DI SINI
                 |--------------------------------------------------------------------------
                 | Sesuaikan nama model dan kolom dengan tabel absensi kamu.
                 | Contoh jika model kamu bernama AbsensiModel:
                 |
                 | \App\Models\AbsensiModel::updateOrCreate(
                 |     [
                 |         'jadwal_id' => $record->getKey(),
                 |         'siswa_id'  => $siswa->getKey(),
                 |         'tanggal'   => now()->toDateString(),
                 |     ],
                 |     [
                 |         'status'           => 'hadir',
                 |         'jam_masuk'        => now()->format('H:i:s'),
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
            });
    }

    private function getSiswaFaceList(): array
    {
        return SiswaModel::query()
            ->whereNotNull('face_descriptor')
            ->get()
            ->map(function (SiswaModel $siswa): ?array {
                $descriptor = $siswa->face_descriptor;

                if (is_string($descriptor)) {
                    $descriptor = json_decode($descriptor, true);
                }

                if (! is_array($descriptor) || count($descriptor) !== 128) {
                    return null;
                }

                return [
                    'id' => $siswa->getKey(),
                    'nama' => $siswa->nama ?? null,
                    'nama_lengkap' => $siswa->nama_lengkap ?? $siswa->nama ?? 'Siswa',
                    'nis' => $siswa->nis ?? null,
                    'face_descriptor' => array_values(array_map('floatval', $descriptor)),
                    'face_image_path' => $siswa->face_image_path ?? null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
