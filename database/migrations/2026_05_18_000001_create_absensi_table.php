<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi', function (Blueprint $table) {
            $table->id();

            // ── Relasi ─────────────────────────────────────────────────────────
            $table->foreignId('siswa_id')
                ->constrained('siswas')
                ->cascadeOnDelete();

            $table->foreignId('kelas_id')
                ->nullable()
                ->constrained('kelas')
                ->nullOnDelete();

            $table->foreignId('jadwal_id')
                ->nullable()
                ->constrained('jadwals')
                ->nullOnDelete();

            // ── Waktu Kehadiran ────────────────────────────────────────────────
            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_keluar')->nullable();

            // ── Status Kehadiran ───────────────────────────────────────────────
            $table->enum('status', ['hadir', 'terlambat', 'izin', 'sakit', 'alfa'])
                ->default('hadir');
            $table->text('keterangan')->nullable();
            $table->string('dokumen_pendukung_path')->nullable(); // bukti izin/sakit

            // ── Face Recognition ───────────────────────────────────────────────
            $table->boolean('verified_by_face')->default(false);
            $table->float('face_confidence', 5, 4)->nullable(); // contoh: 0.9875
            $table->string('face_image_path')->nullable();
            $table->timestamp('face_verified_at')->nullable();

            // ── Audit ──────────────────────────────────────────────────────────
            $table->foreignId('dicatat_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // ── Index ──────────────────────────────────────────────────────────
            $table->index('siswa_id');
            $table->index('kelas_id');
            $table->index('jadwal_id');
            $table->index('tanggal');
            $table->index('status');
            $table->index(['siswa_id', 'tanggal']); // query paling sering: absensi siswa per hari
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi');
    }
};
