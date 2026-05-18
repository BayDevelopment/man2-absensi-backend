<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiModel extends Model
{
    use HasFactory;

    protected $table = 'absensi';

    protected $fillable = [
        'siswa_id',
        'kelas_id',
        'jadwal_id',

        'tanggal',
        'jam_masuk',
        'jam_keluar',

        'status',
        'keterangan',
        'dokumen_pendukung_path',

        'verified_by_face',
        'face_confidence',
        'face_image_path',
        'face_verified_at',

        'dicatat_oleh',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'verified_by_face' => 'boolean',
        'face_confidence' => 'float',
        'face_verified_at' => 'datetime',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(SiswaModel::class, 'siswa_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(KelasModel::class, 'kelas_id');
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(JadwalModel::class, 'jadwal_id');
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
