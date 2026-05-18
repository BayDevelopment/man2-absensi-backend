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
        Schema::create('jam_sekolah', function (Blueprint $table) {
            $table->id();
            $table->time('jam_masuk')->default('07:00:00');
            $table->time('batas_terlambat')->default('07:15:00');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jam_sekolah');
    }
};
