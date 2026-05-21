<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengumumanModel extends Model
{
    protected $table = 'pengumumans';

    protected $fillable = [
        'judul',
        'isi',
        'dibuat_oleh',
        'published_at',
        'expired_at',
        'is_active',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'expired_at'   => 'datetime',
        'is_active'    => 'boolean',
    ];

    // ==================== RELATIONS ====================

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    // ==================== SCOPES ====================

    /** Hanya pengumuman yang aktif */
    public function scopeAktif($query)
    {
        return $query->where('is_active', true);
    }

    /** Hanya yang sudah dipublish (published_at <= sekarang) */
    public function scopePublished($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('published_at')
                ->orWhere('published_at', '<=', now());
        });
    }

    /** Hanya yang belum expired */
    public function scopeBelumExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expired_at')
                ->orWhere('expired_at', '>', now());
        });
    }

    /** Gabungan: aktif + published + belum expired */
    public function scopeTampil($query)
    {
        return $query->aktif()->published()->belumExpired();
    }

    // ==================== ACCESSORS ====================

    /** Cek apakah pengumuman sedang aktif & tampil */
    public function getIsTampilAttribute(): bool
    {
        $now = now();

        $sudahPublish = is_null($this->published_at) || $this->published_at <= $now;
        $belumExpired = is_null($this->expired_at) || $this->expired_at > $now;

        return $this->is_active && $sudahPublish && $belumExpired;
    }
}
