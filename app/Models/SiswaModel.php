<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiswaModel extends Model
{
    use HasFactory;

    protected $table = 'siswas';
    protected $fillable = [
        'user_id',
        'kelas_id',
        'nis',
        'nama_lengkap',
        'jenis_kelamin',
        'no_hp',
        'foto',
        'face_descriptor',
        'face_image',
        'is_face_registered',
        'is_active',
    ];
    protected $casts = [
        'face_descriptor' => 'array',       // otomatis decode JSON menjadi array
        'is_face_registered' => 'boolean',
        'is_active' => 'boolean',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function kelas()
    {
        return $this->belongsTo(KelasModel::class, 'kelas_id');
    }
}
