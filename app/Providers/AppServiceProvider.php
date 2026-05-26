<?php

namespace App\Providers;

use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    // app/Providers/AppServiceProvider.php
    public function boot(): void
    {
        // ✅ BENAR — pakai nama model lengkap
        \App\Models\JadwalModel::created(function ($jadwal) {
            $this->clearJadwalCache($jadwal);
        });

        \App\Models\JadwalModel::updated(function ($jadwal) {
            $this->clearJadwalCache($jadwal);
        });

        \App\Models\JadwalModel::deleted(function ($jadwal) {
            $this->clearJadwalCache($jadwal);
        });

        FilamentAsset::register([]);
    }

    private function clearJadwalCache($jadwal): void
    {
        $namaHari = [
            'Senin',
            'Selasa',
            'Rabu',
            'Kamis',
            'Jumat',
            'Sabtu',
            'Minggu',
        ];

        foreach ($namaHari as $hari) {
            Cache::forget("jadwal_{$jadwal->kelas_id}_{$hari}");
        }
    }
}
