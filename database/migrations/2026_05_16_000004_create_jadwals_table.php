<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('kelas_id')
                ->constrained('kelas')
                ->onDelete('cascade');

            $table->foreignId('mata_pelajaran_id')
                ->nullable()
                ->constrained('mata_pelajarans')
                ->onDelete('set null');

            $table->foreignId('guru_id')
                ->nullable()
                ->constrained('gurus')
                ->onDelete('set null');

            $table->foreignId('tahun_ajaran_id')
                ->nullable()
                ->constrained('tahun_ajarans')
                ->nullOnDelete();

            $table->foreignId('semester_id')
                ->nullable()
                ->constrained('semesters')
                ->nullOnDelete();

            // ✅ Tanggal spesifik jadwal
            $table->date('tanggal')->nullable();

            // Tetap simpan hari untuk memudahkan filter/tampilan
            $table->enum('hari', [
                'Senin',
                'Selasa',
                'Rabu',
                'Kamis',
                'Jumat',
                'Sabtu',
            ]);

            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('ruang')->nullable();

            $table->boolean('is_break')->default(false);
            $table->string('label')->nullable();

            $table->unsignedTinyInteger('urutan')->default(0);

            $table->timestamps();

            // ✅ Biar tidak bentrok jadwal di kelas, tanggal, dan jam yang sama
            $table->index(['kelas_id', 'tanggal']);
            $table->index(['kelas_id', 'hari']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwals');
    }
};
