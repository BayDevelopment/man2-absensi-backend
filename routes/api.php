<?php

use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

Route::post('/login',  [AuthController::class, 'login']);


// Protected routes (butuh token)
Route::middleware('auth:sanctum', 'siswa')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Absensi
    Route::get('/absensi/stats',    [AbsensiController::class, 'stats']);
    Route::get('/absensi/riwayat',  [AbsensiController::class, 'riwayat']);
    Route::post('/absensi/check-in', [AbsensiController::class, 'checkIn']);

    // Dashboard
    Route::get('/dashboard/summary',        [DashboardController::class, 'summary']);
    Route::get('/dashboard/jadwal-hari-ini', [DashboardController::class, 'jadwalHariIni']);
    Route::get('/dashboard/pengumuman',     [DashboardController::class, 'pengumuman']);
});
