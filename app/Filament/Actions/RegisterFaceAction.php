<?php

namespace App\Filament\Actions;

use App\Models\SiswaModel;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RegisterFaceAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'register_face';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(fn($record) => ($record?->is_face_registered ?? false)
                ? 'Daftarkan Ulang Wajah'
                : 'Daftarkan Wajah')
            ->icon('heroicon-o-camera')
            ->color('success')
            ->modalHeading(fn($record) => 'Daftarkan Wajah — ' . ($record?->nama_lengkap ?? 'Siswa'))
            ->modalDescription('Pastikan hanya wajah siswa terkait yang terlihat di kamera.')
            ->modalWidth('2xl')
            ->modalSubmitActionLabel('Simpan Data Wajah')
            ->modalCancelActionLabel('Batal')

            // Penting: data wajib lewat Filament form state.
            ->form([
                Hidden::make('face_descriptor')
                    ->dehydrated()
                    ->required(),

                Hidden::make('face_image_b64')
                    ->dehydrated()
                    ->required(),
            ])

            ->modalContent(fn($record) => view('filament.actions.register-face-modal', [
                'siswa'        => $record,
                'siswaId'      => $record?->getKey(),
                'siswaName'    => $record?->nama_lengkap ?? $record?->nama ?? 'Siswa',
                'existingFace' => (bool) ($record?->is_face_registered ?? false),
                'faceImageUrl' => $record?->face_image_path
                    ? asset('storage/' . $record->face_image_path)
                    : null,
            ]))

            ->action(function (array $data, SiswaModel $record): void {
                $descriptor = $data['face_descriptor'] ?? null;
                $faceImageB64 = $data['face_image_b64'] ?? null;

                $descriptorArr = is_string($descriptor)
                    ? json_decode($descriptor, true)
                    : $descriptor;

                if (!$this->isValidDescriptor($descriptorArr)) {
                    Notification::make()
                        ->title('Gagal menyimpan wajah')
                        ->body('Data descriptor wajah tidak valid. Silakan scan ulang.')
                        ->danger()
                        ->send();

                    $this->halt();
                    return;
                }

                if (!$this->isValidBase64Image($faceImageB64)) {
                    Notification::make()
                        ->title('Gagal menyimpan wajah')
                        ->body('Foto wajah tidak valid atau ukurannya terlalu besar.')
                        ->danger()
                        ->send();

                    $this->halt();
                    return;
                }

                $imagePath = $this->storeFaceImage($record, $faceImageB64);

                $record->update([
                    'face_descriptor' => array_map('floatval', $descriptorArr),
                    'face_image_path' => $imagePath,
                    'is_face_registered' => true,
                    'face_registered_by' => auth()->id(),
                    'face_registered_at' => now(),
                ]);

                Notification::make()
                    ->title('Wajah berhasil didaftarkan')
                    ->body('Data wajah ' . ($record->nama_lengkap ?? 'siswa') . ' berhasil disimpan.')
                    ->success()
                    ->send();
            })

            ->extraModalFooterActions([
                Action::make('hapus_wajah')
                    ->label('Hapus Data Wajah')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus data wajah?')
                    ->modalDescription('Siswa tidak bisa absen dengan verifikasi wajah sampai wajah didaftarkan ulang.')
                    ->visible(fn($record) => (bool) ($record?->is_face_registered ?? false))
                    ->action(function (SiswaModel $record): void {
                        if (
                            $record->face_image_path &&
                            Storage::disk('public')->exists($record->face_image_path)
                        ) {
                            Storage::disk('public')->delete($record->face_image_path);
                        }

                        $record->update([
                            'face_descriptor' => null,
                            'face_image_path' => null,
                            'is_face_registered' => false,
                            'face_registered_by' => null,
                            'face_registered_at' => null,
                        ]);

                        Notification::make()
                            ->title('Data wajah berhasil dihapus')
                            ->warning()
                            ->send();
                    }),
            ]);
    }

    private function isValidDescriptor(mixed $descriptor): bool
    {
        if (!is_array($descriptor) || count($descriptor) !== 128) {
            return false;
        }

        foreach ($descriptor as $value) {
            if (!is_numeric($value)) {
                return false;
            }

            $number = (float) $value;

            if (!is_finite($number) || abs($number) > 10) {
                return false;
            }
        }

        return true;
    }

    private function isValidBase64Image(?string $image): bool
    {
        if (!$image || !Str::startsWith($image, 'data:image/')) {
            return false;
        }

        if (!preg_match('/^data:image\/(jpeg|jpg|png);base64,/', $image)) {
            return false;
        }

        $raw = preg_replace('/^data:image\/(jpeg|jpg|png);base64,/', '', $image);
        $decoded = base64_decode($raw, true);

        if ($decoded === false) {
            return false;
        }

        return strlen($decoded) <= 2 * 1024 * 1024;
    }

    private function storeFaceImage(SiswaModel $record, string $image): string
    {
        $raw = preg_replace('/^data:image\/(jpeg|jpg|png);base64,/', '', $image);
        $decoded = base64_decode($raw, true);

        $filename = 'face-enrollment/' . $record->getKey() . '_' . now()->timestamp . '.jpg';

        if (
            $record->face_image_path &&
            Storage::disk('public')->exists($record->face_image_path)
        ) {
            Storage::disk('public')->delete($record->face_image_path);
        }

        Storage::disk('public')->put($filename, $decoded);

        return $filename;
    }
}
