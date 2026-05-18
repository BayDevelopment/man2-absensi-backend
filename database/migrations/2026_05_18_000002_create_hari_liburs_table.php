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
        Schema::create('hari_liburs', function (Blueprint $table) {
            $table->id();

            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();

            $table->string('nama');

            $table->enum('jenis', [
                'nasional',
                'sekolah',
                'ujian',
                'semester',
                'cuti_bersama',
                'kegiatan_sekolah',
                'rapat_guru',
                'bencana',
                'lainnya'
            ]);

            $table->text('text_lainnya')->nullable();

            $table->boolean('is_libur')->default(true);
            $table->boolean('is_active')->default(true);

            $table->text('keterangan')->nullable();

            $table->foreignId('dibuat_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['tanggal_mulai', 'tanggal_selesai']);
            $table->index('jenis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hari_liburs');
    }
};
