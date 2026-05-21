<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppearanceModel extends Model
{
    protected $table = 'user_appearance_settings';

    protected $fillable = [
        'user_id',
        'tema',
        'bahasa',
        'ukuran_teks',
    ];

    protected $casts = [
        'tema'        => 'string',
        'bahasa'      => 'string',
        'ukuran_teks' => 'string',
    ];

    // ==================== CONSTANTS ====================

    const TEMA = [
        'light'  => 'Terang',
        'dark'   => 'Gelap',
        'system' => 'Ikuti Sistem',
    ];

    const BAHASA = [
        'id' => 'Indonesia',
        'en' => 'English',
    ];

    const UKURAN_TEKS = [
        'small'  => 'Kecil',
        'normal' => 'Normal',
        'large'  => 'Besar',
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
                'tema'        => 'light',
                'bahasa'      => 'id',
                'ukuran_teks' => 'normal',
            ]
        );
    }
}
