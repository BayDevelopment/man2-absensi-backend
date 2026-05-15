<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
