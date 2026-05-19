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

#[Fillable(['name', 'email', 'nisn', 'password', 'role'])]
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

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(KelasModel::class, 'kelas_id');
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(AbsensiModel::class, 'siswa_id');
    }

    public function jadwalMengajar(): HasMany
    {
        return $this->hasMany(JadwalModel::class, 'guru_id');
    }

    public function siswa()
    {
        return $this->hasOne(SiswaModel::class);
    }

    public function guru()
    {
        return $this->hasMany(GuruModel::class, 'user_id');
    }
}
