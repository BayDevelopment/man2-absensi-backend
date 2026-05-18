<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gurus', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('jabatan_id')
                ->nullable()
                ->constrained('jabatans')
                ->nullOnDelete();

            $table->string('nip', 30)->nullable()->unique();
            $table->string('nama_lengkap');
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('email')->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->string('foto')->nullable();

            $table->json('face_descriptor')->nullable();
            $table->string('face_image')->nullable();
            $table->boolean('is_face_registered')->default(false);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('jabatan_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gurus');
    }
};
