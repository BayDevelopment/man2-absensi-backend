<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuruMapelModel extends Model
{
    use HasFactory;

    protected $table = 'guru_mata_pelajaran';

    protected $fillable = [
        'guru_id',
        'mata_pelajaran_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function guru()
    {
        return $this->belongsTo(GuruModel::class, 'guru_id');
    }

    public function mataPelajaran()
    {
        return $this->belongsTo(MataPelajaran::class);
    }
}
