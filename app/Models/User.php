<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;  // ← tambah ini
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'nisn', 'password', 'role', 'two_factor',  'notif_login',  'logout_otomatis',])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, FilamentUser  // ← tambah FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    protected $attributes = [
        'role' => 'siswa',
    ];

    // =========================================================================
    // Filament Access
    // =========================================================================

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'guru'])
            && $this->hasVerifiedEmail();  // ← wajib verified
    }

    // =========================================================================
    // Relationships
    // =========================================================================

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
    // Relasi ke tabel siswas
    public function siswa()
    {
        return $this->hasOne(SiswaModel::class, 'user_id');
    }

    // Relasi ke settings
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
