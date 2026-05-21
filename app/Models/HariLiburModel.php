<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HariLiburModel extends Model
{
    protected $table = 'hari_liburs';
    protected $fillable = [
        'tanggal_mulai',
        'tanggal_selesai',
        'nama',
        'jenis',
        'text_lainnya',
        'is_libur',
        'is_active',
        'keterangan',
        'dibuat_oleh',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'is_libur' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLibur(Builder $query): Builder
    {
        return $query->where('is_libur', true);
    }

    public function scopeTidakLibur(Builder $query): Builder
    {
        return $query->where('is_libur', false);
    }

    public function scopeBerlakuPadaTanggal(Builder $query, string $tanggal): Builder
    {
        return $query
            ->whereDate('tanggal_mulai', '<=', $tanggal)
            ->where(function (Builder $query) use ($tanggal) {
                $query->whereNull('tanggal_selesai')
                    ->orWhereDate('tanggal_selesai', '>=', $tanggal);
            });
    }
}
