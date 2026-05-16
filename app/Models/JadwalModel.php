<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JadwalModel extends Model
{
    protected $table    = 'jadwals';
    protected $fillable = [
        'kelas_id',
        'mata_pelajaran_id',
        'guru_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'ruang',
    ];

    protected $casts = [
        'jam_mulai'   => 'string',
        'jam_selesai' => 'string',
    ];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(KelasModel::class, 'kelas_id');
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'mata_pelajaran_id');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(AbsensiModel::class, 'jadwal_id');
    }
}
