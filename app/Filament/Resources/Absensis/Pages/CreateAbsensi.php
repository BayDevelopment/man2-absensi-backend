<?php

namespace App\Filament\Resources\Absensis\Pages;

use App\Filament\Resources\Absensis\AbsensiResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateAbsensi extends CreateRecord
{
    protected static string $resource = AbsensiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['dicatat_oleh'] = Auth::id();

        if (empty($data['jam_keluar']) && !empty($data['jadwal_id'])) {
            $data['jam_keluar'] = AbsensiResource::getJamKeluarOtomatis(
                $data['jadwal_id'],
                $data['kelas_id'] ?? null,
                $data['tanggal'] ?? null,
            );
        }

        if (empty($data['keterangan'])) {
            $data['keterangan'] = static::keteranganOtomatis($data);
        }

        return $data;
    }

    private static function keteranganOtomatis(array $data): ?string
    {
        return match ($data['status'] ?? '') {
            'terlambat' => 'Absen terlambat (dicatat oleh guru/admin)',
            'izin'      => 'Tidak hadir dengan izin',
            'sakit'     => 'Tidak hadir karena sakit',
            default     => null,
        };
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Berhasil')
            ->body('Absensi berhasil ditambahkan.')
            ->success();
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Create')
                ->icon('heroicon-o-check-circle')
                ->color('primary'),

            $this->getCreateAnotherFormAction()
                ->label('Create & Create Another')
                ->icon('heroicon-o-plus-circle')
                ->color('success'),

            $this->getCancelFormAction()
                ->label('Cancel')
                ->url($this->getResource()::getUrl('index'))
                ->icon('heroicon-o-x-mark')
                ->color('gray'),
        ];
    }
}
