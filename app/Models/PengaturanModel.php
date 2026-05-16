<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengaturanModel extends Model
{
    protected $table    = 'pengaturan';
    protected $fillable = [
        'nama_sekolah',
        'logo',
        'alamat',
        'kepala_sekolah',
    ];

    // Helper ambil setting (selalu satu baris)
    public static function getSetting(): ?self
    {
        return static::first();
    }
}
