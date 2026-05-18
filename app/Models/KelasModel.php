<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KelasModel extends Model
{
    use HasFactory;

    protected $table = 'kelas';

    protected $fillable = [
        'nama_kelas',
        'tingkat',
        'jurusan',
        'wali_kelas_id',
        'tahun_ajaran_id',
    ];

    public function siswas(): HasMany
    {
        return $this->hasMany(SiswaModel::class, 'kelas_id');
    }

    public function jadwals(): HasMany
    {
        return $this->hasMany(JadwalModel::class, 'kelas_id');
    }

    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(GuruModel::class, 'wali_kelas_id');
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaranModel::class, 'tahun_ajaran_id');
    }
}
