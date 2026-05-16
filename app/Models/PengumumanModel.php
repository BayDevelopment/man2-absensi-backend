<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengumumanModel extends Model
{
    protected $table    = 'pengumumans';
    protected $fillable = ['judul', 'isi'];
}
