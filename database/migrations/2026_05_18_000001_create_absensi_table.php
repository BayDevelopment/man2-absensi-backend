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

            // Relasi utama
            $table->foreignId('siswa_id')->constrained('siswas')->onDelete('cascade');
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->onDelete('set null');
            $table->foreignId('jadwal_id')->nullable()->constrained('jadwals')->onDelete('set null');
            $table->foreignId('tahun_ajaran_id')->nullable()->constrained('tahun_ajarans')->nullOnDelete();

            // ✅ FIX: Tambah semester_id untuk filter laporan per semester
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->nullOnDelete();

            // Waktu
            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_keluar')->nullable();

            // Status
            $table->enum('status', ['hadir', 'terlambat', 'izin', 'sakit', 'alfa'])->default('alfa');
            $table->text('keterangan')->nullable();
            $table->string('dokumen_pendukung_path')->nullable();

            // Face Recognition
            $table->boolean('verified_by_face')->default(false);

            // ✅ FIX: Ganti decimal(5,4) → float, range 0.0–1.0 lebih fleksibel
            //    decimal(5,4) hanya bisa 0.0000–0.9999 dan tidak bisa simpan 1.0000
            $table->float('face_confidence')->nullable();

            $table->string('face_image_path')->nullable(); // foto wajah saat absen
            $table->timestamp('face_verified_at')->nullable();

            // Audit
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // 1 siswa hanya bisa absen 1x per jadwal per hari
            $table->unique(['siswa_id', 'jadwal_id', 'tanggal']);

            // ✅ TAMBAHAN: index untuk query laporan yang sering dipakai
            $table->index(['tanggal', 'kelas_id']);
            $table->index(['siswa_id', 'tanggal']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi');
    }
};
