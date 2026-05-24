<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAppearanceSetting extends Model
{
    protected $table = 'user_appearance_settings';
    protected $fillable = ['user_id', 'tema', 'bahasa', 'ukuran_teks'];
}
