<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiswaModel extends Model
{
    protected $table = 'siswas';

    protected $fillable = [
        'user_id',
        'kelas_id',
        'angkatan_id',
        'nis',
        'status_siswa',
        'nama_lengkap',
        'jenis_kelamin',
        'agama',
        'tempat_lahir',
        'tanggal_lahir',
        'alamat',
        'no_hp',
        'nama_ayah',
        'nama_ibu',
        'no_wali',
        'foto',

        // Face Recognition
        'face_descriptor',
        'face_image_path',
        'is_face_registered',
        'face_registered_by',
        'face_registered_at',

        'is_active',
    ];

    protected $casts = [
        'face_descriptor' => 'array',
        'is_face_registered' => 'boolean',
        'is_active' => 'boolean',
        'tanggal_lahir' => 'date',
        'face_registered_at' => 'datetime',
    ];
    protected $hidden = [
        'face_descriptor',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(KelasModel::class, 'kelas_id');
    }

    public function angkatan(): BelongsTo
    {
        return $this->belongsTo(AngkatanModel::class, 'angkatan_id');
    }
    public function faceRegisteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'face_registered_by');
    }
    public function absensis()
    {
        return $this->hasMany(AbsensiModel::class, 'siswa_id', 'id');
    }
}
