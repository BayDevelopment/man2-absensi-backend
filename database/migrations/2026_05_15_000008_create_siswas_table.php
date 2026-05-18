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
        Schema::create('siswas', function (Blueprint $table) {
            $table->id();

            // Relasi
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->onDelete('set null');
            $table->foreignId('angkatan_id')->nullable()->constrained('angkatans')->nullOnDelete();
            // Identitas akademik
            $table->string('nis', 20)->unique();
            $table->string('status_siswa')->default('aktif');
            // Data diri
            $table->string('nama_lengkap');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('agama')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->text('alamat')->nullable();

            // Kontak
            $table->string('no_hp', 20)->nullable();

            // Data orang tua / wali
            $table->string('nama_ayah')->nullable();
            $table->string('nama_ibu')->nullable();
            $table->string('no_wali', 20)->nullable();

            // Foto & Face Recognition
            $table->string('foto')->nullable();
            $table->json('face_descriptor')->nullable();
            $table->string('face_image')->nullable();
            $table->boolean('is_face_registered')->default(false);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siswas');
    }
};
