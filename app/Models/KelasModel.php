<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KelasModel extends Model
{
    use HasFactory;

    protected $table = 'kelas';

    /**
     * Kolom yang dapat diisi massal.
     */
    protected $fillable = [
        'nama_kelas',
        'tingkat',
        'jurusan',
    ];

    public function siswas()
    {
        return $this->hasMany(SiswaModel::class, 'kelas_id');
    }
    public function jadwals(): HasMany
    {
        return $this->hasMany(JadwalModel::class, 'kelas_id');
    }
}
