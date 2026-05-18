<?php

namespace App\Filament\Widgets;

use App\Models\AbsensiModel;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class AbsensiStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = Carbon::today();

        $startWeek = Carbon::now()->startOfWeek()->startOfDay();
        $endWeek   = Carbon::now()->endOfWeek()->endOfDay();

        /*
    |--------------------------------------------------------------------------
    | Data Absensi Hari Ini
    |--------------------------------------------------------------------------
    */
        $todayCounts = AbsensiModel::query()
            ->whereDate('tanggal', '=', $today->toDateString(), 'and')
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $hadirHariIni = (int) $todayCounts->get('hadir', 0);
        $sakitHariIni = (int) $todayCounts->get('sakit', 0);
        $izinHariIni = (int) $todayCounts->get('izin', 0);
        $alfaHariIni = (int) $todayCounts->get('alfa', 0);
        $terlambatHariIni = (int) $todayCounts->get('terlambat', 0);

        $totalHariIni = $todayCounts->sum();

        /*
    |--------------------------------------------------------------------------
    | Data Absensi Minggu Ini
    |--------------------------------------------------------------------------
    */
        $weekCounts = AbsensiModel::query()
            ->whereBetween('tanggal', [
                $startWeek->toDateString(),
                $endWeek->toDateString(),
            ], 'and', false)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $alfaMingguIni = (int) $weekCounts->get('alfa', 0);

        /*
    |--------------------------------------------------------------------------
    | Grafik Mini 7 Hari Terakhir Untuk Card
    |--------------------------------------------------------------------------
    */
        $startChart = Carbon::today()->subDays(6);
        $endChart = Carbon::today();

        $chartRows = AbsensiModel::query()
            ->whereDate('tanggal', '>=', $startChart->toDateString(), 'and')
            ->whereDate('tanggal', '<=', $endChart->toDateString(), 'and')
            ->selectRaw('DATE(tanggal) as tanggal_absensi, status, COUNT(*) as total')
            ->groupByRaw('DATE(tanggal), status')
            ->get()
            ->mapWithKeys(function ($row) {
                return [
                    $row->tanggal_absensi . '_' . $row->status => (int) $row->total,
                ];
            });

        $charts = [
            'hadir' => [],
            'sakit' => [],
            'izin' => [],
            'alfa' => [],
            'terlambat' => [],
        ];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();

            $charts['hadir'][] = (int) $chartRows->get($date . '_hadir', 0);
            $charts['sakit'][] = (int) $chartRows->get($date . '_sakit', 0);
            $charts['izin'][] = (int) $chartRows->get($date . '_izin', 0);
            $charts['alfa'][] = (int) $chartRows->get($date . '_alfa', 0);
            $charts['terlambat'][] = (int) $chartRows->get($date . '_terlambat', 0);
        }

        return [
            Stat::make('Hadir Hari Ini', $hadirHariIni)
                ->description("Dari {$totalHariIni} total absensi hari ini")
                ->descriptionIcon('heroicon-o-check-circle')
                ->chart($charts['hadir'])
                ->color('success')
                ->icon('heroicon-o-user-group'),

            Stat::make('Sakit Hari Ini', $sakitHariIni)
                ->description('Tidak hadir karena sakit')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->chart($charts['sakit'])
                ->color($sakitHariIni > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-heart'),

            Stat::make('Izin Hari Ini', $izinHariIni)
                ->description('Tidak hadir dengan izin')
                ->descriptionIcon('heroicon-o-document-text')
                ->chart($charts['izin'])
                ->color($izinHariIni > 0 ? 'info' : 'success')
                ->icon('heroicon-o-document-text'),

            Stat::make('Alfa Hari Ini', $alfaHariIni)
                ->description('Tidak hadir tanpa keterangan')
                ->descriptionIcon('heroicon-o-x-circle')
                ->chart($charts['alfa'])
                ->color($alfaHariIni > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-no-symbol'),

            Stat::make('Terlambat Hari Ini', $terlambatHariIni)
                ->description('Masuk tetapi terlambat')
                ->descriptionIcon('heroicon-o-clock')
                ->chart($charts['terlambat'])
                ->color($terlambatHariIni > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-clock'),

            Stat::make('Alfa Minggu Ini', $alfaMingguIni)
                ->description('Grafik alfa 7 hari terakhir')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->chart($charts['alfa'])
                ->color($alfaMingguIni > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-calendar-days'),
        ];
    }
}
