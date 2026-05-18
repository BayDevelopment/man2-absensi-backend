<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JabatanModel extends Model
{
    use HasFactory;

    protected $table = 'jabatans';

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'nama',
        'bisa_mengajar',
    ];

    /**
     * Cast attributes
     */
    protected $casts = [
        'bisa_mengajar' => 'boolean',
    ];

    /**
     * Relasi: jabatan bisa dimiliki oleh banyak guru
     */
    public function gurus()
    {
        return $this->hasMany(GuruModel::class);
    }
}
