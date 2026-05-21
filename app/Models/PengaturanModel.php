<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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

    protected static function boot(): void
    {
        parent::boot();

        // Hapus logo lama ketika logo diganti (update)
        static::updating(function (self $model) {
            if ($model->isDirty('logo') && $model->getOriginal('logo')) {
                Storage::disk('public')->delete($model->getOriginal('logo'));
            }
        });

        // Hapus logo ketika record dihapus
        static::deleting(function (self $model) {
            if ($model->logo) {
                Storage::disk('public')->delete($model->logo);
            }
        });
    }
}
