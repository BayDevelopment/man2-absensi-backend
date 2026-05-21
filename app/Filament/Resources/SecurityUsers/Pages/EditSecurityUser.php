<?php

namespace App\Filament\Resources\SecurityUsers\Pages;

use App\Filament\Resources\SecurityUsers\SecurityUserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSecurityUser extends EditRecord
{
    protected static string $resource = SecurityUserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Paksa user_id tetap dari record asli, tidak bisa diubah
        $data['user_id'] = $this->record->user_id;

        $user = User::where('id', $data['user_id'])->first();

        abort_if(
            !$user || $user->role !== 'siswa',
            403,
            'User yang dipilih bukan siswa.'
        );

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Berhasil Diupdate')
            ->body('Security user berhasil diperbarui.')
            ->success();
    }
    protected function getHeaderActions(): array
    {
        return [
            // ViewAction::make(),
            // DeleteAction::make(),
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
