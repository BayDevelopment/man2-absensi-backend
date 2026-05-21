<?php

use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\SiswaFaceController;
use Illuminate\Support\Facades\Route;

Route::post('/login',  [AuthController::class, 'login']);
Route::get('/login',  [AuthController::class, 'index']);


// Protected routes (butuh token)
Route::middleware('auth:sanctum', 'siswa')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Absensi
    Route::get('/absensi', [AbsensiController::class, 'index']);
    Route::post('/absensi/masuk', [AbsensiController::class, 'absenMasuk']);
    Route::post('/absensi/keluar', [AbsensiController::class, 'absenKeluar']);
    Route::post('/absensi/status', [AbsensiController::class, 'simpanStatus']);
    Route::get('/absensi/jadwal-by-tanggal', [AbsensiController::class, 'getJadwalByTanggal']);

    Route::post('/siswa/{siswa}/register-face', [SiswaFaceController::class, 'registerFace']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
    Route::get('/dashboard/jadwal-hari-ini', [DashboardController::class, 'jadwalHariIni']);
    Route::get('/dashboard/pengumuman', [DashboardController::class, 'pengumuman']);
});
