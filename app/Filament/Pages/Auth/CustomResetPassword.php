<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\PasswordReset\ResetPassword;

class CustomResetPassword extends ResetPassword
{
    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Reset Password';
    }

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Reset Password';
    }
}
