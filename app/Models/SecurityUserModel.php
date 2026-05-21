<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityUserModel extends Model
{
    protected $table = 'user_security_settings';

    protected $fillable = [
        'user_id',
        'two_factor',
        'notif_login',
        'logout_otomatis',
    ];

    protected $casts = [
        'two_factor'      => 'boolean',
        'notif_login'     => 'boolean',
        'logout_otomatis' => 'boolean',
    ];

    // ==================== RELATIONS ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== STATIC HELPERS ====================

    public static function getOrCreate(int $userId): static
    {
        return static::firstOrCreate(
            ['user_id' => $userId],
            [
                'two_factor'      => false,
                'notif_login'     => true,
                'logout_otomatis' => false,
            ]
        );
    }
}
