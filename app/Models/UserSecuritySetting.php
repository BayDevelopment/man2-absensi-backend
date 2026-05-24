<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSecuritySetting extends Model
{
    protected $table = 'user_security_settings';
    protected $fillable = ['user_id', 'two_factor', 'notif_login', 'logout_otomatis'];
    protected $casts = [
        'two_factor' => 'boolean',
        'notif_login' => 'boolean',
        'logout_otomatis' => 'boolean'
    ];
}
