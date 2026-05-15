<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsensiModel extends Model
{
    use HasFactory;

    protected $table = 'absensi';

    protected $fillable = [
        'siswa_id',
        'kelas_id',
        'tanggal',
        'jam_masuk',
        'status',
        'keterangan',
        'verified_by_face',
        'face_confidence',
        'dicatat_oleh',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jam_masuk' => 'time',
        'verified_by_face' => 'boolean',
        'face_confidence' => 'float',
    ];

    public function siswa()
    {
        return $this->belongsTo(SiswaModel::class);
    }

    /**
     * Relasi: absensi milik satu kelas (opsional)
     */
    public function kelas()
    {
        return $this->belongsTo(KelasModel::class);
    }

    public function pencatat()
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
