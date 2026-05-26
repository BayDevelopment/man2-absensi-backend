<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;

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
            ->modalHeading(fn($record) => 'Daftarkan Wajah — ' . ($record?->nama_lengkap ?? $record?->nama ?? 'Siswa'))
            ->modalDescription('Aktifkan kamera, scan wajah siswa, lalu simpan data wajah.')
            ->modalWidth('3xl')
            ->modalCancelActionLabel('Tutup')
            ->modalSubmitAction(false)   // tombol submit di-handle oleh view sendiri
            ->modalContent(fn($record) => view('filament.actions.register-face-modal', [
                'siswa'        => $record,
                'siswaId'      => $record?->getKey(),
                'siswaName'    => $record?->nama_lengkap ?? $record?->nama ?? 'Siswa',
                'existingFace' => (bool) ($record?->is_face_registered ?? false),
                'faceImageUrl' => $record?->face_image_path
                    ? asset('storage/' . $record->face_image_path)
                    : null,
            ]));
        // Tidak ada ->action() — submit ditangani langsung dari view via Livewire call
    }
}
