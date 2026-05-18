<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kelas');
            $table->enum('tingkat', ['X', 'XI', 'XII']);
            $table->enum('jurusan', [
                'Tahfiz',
                'Olimpiade',
                'Olahraga & Seni',
                'Multimedia',
                'Linguistik',
            ])->nullable();
            $table->foreignId('wali_kelas_id')
                ->nullable()
                ->constrained('gurus')
                ->nullOnDelete();
            $table->foreignId('tahun_ajaran_id')
                ->nullable()
                ->constrained('tahun_ajarans')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};
