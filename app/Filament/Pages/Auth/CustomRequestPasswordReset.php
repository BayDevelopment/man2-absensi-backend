<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Filament\Schemas\Schema; // ✅ Ganti Form dengan Schema

class CustomRequestPasswordReset extends RequestPasswordReset
{
    public function request(): void
    {
        $data = $this->form->getState();
        $email = $data['email'];

        $user = User::query()->where('email', $email)->first();

        if (!$user || $user->role !== 'admin') {
            Notification::make()
                ->title('Permintaan diproses')
                ->body('Jika email terdaftar dan memiliki akses, link reset password akan dikirim.')
                ->success()
                ->send();

            return;
        }

        $status = Password::broker(config('filament.auth.password_broker', 'users'))
            ->sendResetLink(['email' => $email]);

        Notification::make()
            ->title('Permintaan diproses')
            ->body('Jika email terdaftar dan memiliki akses, link reset password akan dikirim.')
            ->success()
            ->send();
    }

    public function form(Schema $schema): Schema // ✅ Ganti Form dengan Schema
    {
        return $schema
            ->schema([
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->autocomplete()
                    ->autofocus(),
            ]);
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Lupa Password';
    }

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Lupa Password?';
    }

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'Masukkan email Anda dan kami akan kirimkan link untuk reset password.';
    }

    protected function getFormActions(): array
    {
        return [
            $this->getRequestFormAction()
                ->label('Kirim Link Reset'),
        ];
    }

    public function hasFullWidthFormActions(): bool
    {
        return true;
    }
}
