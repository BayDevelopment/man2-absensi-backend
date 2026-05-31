<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'nisn',
    'password',
    'two_factor',
    'notif_login',
    'logout_otomatis',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected $attributes = [
        'role' => 'siswa',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'guru'], true)
            && $this->hasVerifiedEmail();
    }

    public function sendPasswordResetNotification($token): void
    {
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return URL::temporarySignedRoute(
                'filament.admin.auth.password-reset.reset',
                now()->addMinutes(config('auth.passwords.users.expire', 60)),
                [
                    'token' => $token,
                    'email' => $user->email,
                ]
            );
        });

        $this->notify(new ResetPassword($token));
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(AbsensiModel::class, 'siswa_id');
    }

    public function jadwalMengajar(): HasMany
    {
        return $this->hasMany(JadwalModel::class, 'guru_id');
    }

    public function guru()
    {
        return $this->hasMany(GuruModel::class, 'user_id');
    }

    public function siswa()
    {
        return $this->hasOne(SiswaModel::class, 'user_id');
    }

    public function notificationSetting()
    {
        return $this->hasOne(NotificationModel::class, 'user_id');
    }

    public function securitySetting()
    {
        return $this->hasOne(UserSecuritySetting::class, 'user_id');
    }

    public function appearanceSetting()
    {
        return $this->hasOne(UserAppearanceSetting::class, 'user_id');
    }

    public function userSessions()
    {
        return $this->hasMany(UserSession::class, 'user_id');
    }
}
