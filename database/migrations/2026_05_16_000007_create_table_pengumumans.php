<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ FIX: Hapus prefix 'table_', nama tabel jadi 'pengumumans'
        Schema::create('pengumumans', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('isi');

            // ✅ TAMBAHAN: relasi ke user yang membuat pengumuman
            $table->foreignId('dibuat_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // ✅ TAMBAHAN: tanggal publish & expired agar bisa dijadwalkan
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expired_at')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('is_active');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengumumans');
    }
};
