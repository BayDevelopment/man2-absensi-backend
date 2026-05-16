<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JamSekolahModel extends Model
{
    protected $table    = 'jam_sekolah';
    protected $fillable = ['jam_masuk', 'batas_terlambat', 'aktif'];

    protected $casts = [
        'aktif'           => 'boolean',
        'jam_masuk'       => 'string',
        'batas_terlambat' => 'string',
    ];

    // Ambil konfigurasi aktif (singleton helper)
    public static function aktif(): ?static
    {
        return static::where('aktif', true)->first();
    }
}
