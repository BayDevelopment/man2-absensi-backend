<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MataPelajaran extends Model
{
    protected $table    = 'mata_pelajarans';
    protected $fillable = ['nama', 'kode'];

    public function jadwals(): HasMany
    {
        return $this->hasMany(JadwalModel::class, 'mata_pelajaran_id');
    }
}
