<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SemesterModel extends Model
{
    protected $table = 'semesters';

    protected $fillable = [
        'tahun_ajaran_id',
        'nama',
        'tanggal_mulai',
        'tanggal_selesai',
        'is_active',
    ];

    protected $casts = [
        'tanggal_mulai'   => 'date',
        'tanggal_selesai' => 'date',
        'is_active'       => 'boolean',
    ];

    // ─── Enum values ──────────────────────────────────────────
    const NAMA_GANJIL = 'ganjil';
    const NAMA_GENAP  = 'genap';

    public static function namaOptions(): array
    {
        return [
            self::NAMA_GANJIL => 'Ganjil',
            self::NAMA_GENAP  => 'Genap',
        ];
    }

    // ─── Relationships ─────────────────────────────────────────
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaranModel::class, 'tahun_ajaran_id');
    }

    // ─── Accessors ─────────────────────────────────────────────

    /**
     * Label nama semester yang dikapitalisasi.
     * Contoh: "ganjil" → "Ganjil"
     */
    public function getNamaLabelAttribute(): string
    {
        return ucfirst($this->nama);
    }

    /**
     * Durasi semester dalam hari.
     */
    public function getDurasiHariAttribute(): int
    {
        return $this->tanggal_mulai->diffInDays($this->tanggal_selesai);
    }

    /**
     * Cek apakah semester sedang berjalan hari ini.
     */
    public function getSedangBerjalanAttribute(): bool
    {
        $today = now()->toDateString();

        return $this->tanggal_mulai->toDateString() <= $today
            && $this->tanggal_selesai->toDateString() >= $today;
    }
}
