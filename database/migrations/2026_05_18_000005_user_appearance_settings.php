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
        Schema::create('user_appearance_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('tema', ['light', 'dark', 'system'])
                ->default('light');

            $table->enum('bahasa', ['id', 'en'])
                ->default('id');

            $table->enum('ukuran_teks', ['small', 'normal', 'large'])
                ->default('normal');

            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_appearance_settings');
    }
};
