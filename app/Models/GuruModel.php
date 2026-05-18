<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuruModel extends Model
{
    use HasFactory;

    protected $table = 'gurus';

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'user_id',
        'jabatan_id',
        'nip',
        'nama_lengkap',
        'jenis_kelamin',
        'email',
        'no_hp',
        'alamat',
        'foto',
        'face_descriptor',
        'face_image',
        'is_face_registered',
        'is_active',
    ];

    /**
     * Cast attributes
     */
    protected $casts = [
        'is_face_registered' => 'boolean',
        'is_active' => 'boolean',
        'face_descriptor' => 'array', // karena tipe json
    ];

    /**
     * Relationship: Guru belongs to User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: Guru belongs to Jabatan (Position)
     */
    public function jabatan()
    {
        return $this->belongsTo(JabatanModel::class);
    }

    /**
     * Accessor untuk menampilkan jenis kelamin secara lengkap
     */
    public function getJenisKelaminTextAttribute()
    {
        return $this->jenis_kelamin === 'L' ? 'Laki-laki' : ($this->jenis_kelamin === 'P' ? 'Perempuan' : null);
    }

    /**
     * Scope untuk guru yang aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
