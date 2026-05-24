<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JadwalModel extends Model
{
    protected $table = 'jadwals';

    protected $fillable = [
        'kelas_id',
        'mata_pelajaran_id',
        'guru_id',
        'tahun_ajaran_id',
        'semester_id',
        'tanggal',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'ruang',
        'is_break',
        'label',
        'urutan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'is_break' => 'boolean',
        'urutan'   => 'integer',
    ];

    const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

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
        return $this->belongsTo(GuruModel::class, 'guru_id');
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaranModel::class, 'tahun_ajaran_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(SemesterModel::class, 'semester_id');
    }
}
