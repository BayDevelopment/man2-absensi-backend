<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AngkatanModel extends Model
{
    protected $table = 'angkatans';

    protected $fillable = [
        'nama',
        'tahun_masuk',
        'tahun_lulus',
        'is_active',
    ];

    protected $casts = [
        'tahun_masuk' => 'integer',
        'tahun_lulus' => 'integer',
        'is_active' => 'boolean',
    ];
}
