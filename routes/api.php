<?php

use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\JadwalController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RiwayatController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SiswaFaceController;
use Illuminate\Support\Facades\Route;

// ── Publik: login & pengaturan sekolah (throttle biar tidak di-spam) ────────
Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/pengaturan', [SettingController::class, 'pengaturan']);
    Route::get('/login',      [AuthController::class,   'index']);
});

Route::middleware(['throttle:10,1'])->group(function () {
    Route::post('/login',      [AuthController::class, 'login']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
});

// ── Protected: semua butuh token Sanctum + middleware siswa ─────────────────
// FIX: middleware pakai array, bukan dua string terpisah
Route::middleware(['auth:sanctum', 'siswa'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);
    Route::get('/notifications', [NotificationController::class, 'index']);

    // Dashboard
    Route::get('/dashboard',                   [DashboardController::class, 'index']);
    Route::get('/dashboard/summary',           [DashboardController::class, 'summary']);
    Route::get('/dashboard/jadwal-hari-ini',   [DashboardController::class, 'jadwalHariIni']);
    Route::get('/dashboard/pengumuman',        [DashboardController::class, 'pengumuman']);

    // Absensi
    Route::get('/absensi',                        [AbsensiController::class, 'index']);
    Route::post('/absensi/masuk',                 [AbsensiController::class, 'absenMasuk']);
    Route::post('/absensi/keluar',                [AbsensiController::class, 'absenKeluar']);
    Route::post('/absensi/status',                [AbsensiController::class, 'simpanStatus']);
    Route::get('/absensi/jadwal-by-tanggal',      [AbsensiController::class, 'getJadwalByTanggal']);
    Route::post('/siswa/{siswa}/register-face',   [SiswaFaceController::class, 'registerFace']);

    // Riwayat
    Route::get('/riwayat', [RiwayatController::class, 'index']);

    // Jadwal
    Route::get('/jadwal',                     [JadwalController::class, 'index']);
    Route::get('/jadwal/tanggal/{tanggal}',   [JadwalController::class, 'byTanggal']);

    // Profil
    Route::get('/profil',       [ProfileController::class, 'show']);
    Route::put('/profil',       [ProfileController::class, 'update']);
    Route::post('/profil/foto', [ProfileController::class, 'uploadFoto']);

    // Settings (umum)
    Route::get('/settings',              [SettingController::class, 'index']);
    Route::put('/settings/notifikasi',   [SettingController::class, 'updateNotifikasi']);
    Route::put('/settings/keamanan',     [SettingController::class, 'updateKeamanan']);
    Route::put('/settings/tampilan',     [SettingController::class, 'updateTampilan']);
    Route::delete('/settings/sesi/{id}', [SettingController::class, 'destroySession']);
    Route::delete('/settings/sesi',      [SettingController::class, 'destroyAllSessions']);

    // FIX: ganti password dapat throttle tersendiri (defense in depth)
    Route::middleware(['throttle:5,1'])->group(function () {
        Route::put('/settings/password', [SettingController::class, 'updatePassword']);
    });
});
