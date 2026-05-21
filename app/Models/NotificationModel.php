<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationModel extends Model
{
    protected $table = 'user_notification_settings';

    protected $fillable = [
        'user_id',
        'kehadiran',
        'pengumuman',
        'jadwal',
        'nilai',
    ];

    protected $casts = [
        'kehadiran'  => 'boolean',
        'pengumuman' => 'boolean',
        'jadwal'     => 'boolean',
        'nilai'      => 'boolean',
    ];

    // ==================== RELATIONS ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== STATIC HELPERS ====================
    /** Ambil atau buat setting notifikasi untuk user tertentu */
    public static function getOrCreate(int $userId): static
    {
        return static::firstOrCreate(
            ['user_id' => $userId],
            [
                'kehadiran'  => true,
                'pengumuman' => true,
                'jadwal'     => false,
                'nilai'      => true,
            ]
        );
    }
}
