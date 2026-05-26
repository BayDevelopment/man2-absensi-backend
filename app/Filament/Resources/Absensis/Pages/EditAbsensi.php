<?php

namespace App\Filament\Resources\Absensis\Pages;

use App\Filament\Resources\Absensis\AbsensiResource;
use Filament\Actions\Action;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class EditAbsensi extends EditRecord
{
    protected static string $resource = AbsensiResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['dicatat_oleh'] = Auth::id();

        if (empty($data['jam_keluar']) && !empty($data['jadwal_id'])) {
            $data['jam_keluar'] = AbsensiResource::getJamKeluarOtomatis(
                $data['jadwal_id'],
                $data['kelas_id'] ?? null,
                $data['tanggal'] ?? null,
            );
        }

        return $data;
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Admin boleh edit kapan saja
        if (Auth::user()->hasRole('admin')) {
            return;
        }

        $tanggalAbsen = Carbon::parse($this->record->tanggal)->startOfDay();
        $kemarin      = Carbon::yesterday()->startOfDay();

        // Jika tanggal absen lebih lama dari kemarin → tolak
        if ($tanggalAbsen->lt($kemarin)) {
            Notification::make()
                ->title('Tidak Dapat Diedit')
                ->body('Absensi hanya dapat diedit maksimal H+1 (kemarin). Data ini sudah terkunci.')
                ->danger()
                ->persistent()
                ->send();

            $this->redirect(static::getResource()::getUrl('index'));
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Berhasil Diupdate')
            ->body('Absensi berhasil diperbarui.')
            ->success();
    }

    protected function getHeaderActions(): array
    {
        return [
            ForceDeleteAction::make(),
            RestoreAction::make(),
            Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Save Changes')
                ->icon('heroicon-o-check-circle')
                ->color('primary'),

            $this->getCancelFormAction()
                ->label('Cancel')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->url(static::getResource()::getUrl('index')),
        ];
    }
}
