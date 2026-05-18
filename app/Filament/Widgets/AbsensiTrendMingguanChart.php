<?php

namespace App\Filament\Widgets;

use App\Models\AbsensiModel;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class AbsensiTrendMingguanChart extends ChartWidget
{
    protected ?string $heading = 'Grafik Absensi 7 Hari Terakhir';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '30s';

    protected function getData(): array
    {
        $startDate = Carbon::today()->subDays(6)->startOfDay();
        $endDate = Carbon::today()->endOfDay();

        $period = CarbonPeriod::create($startDate, $endDate);

        $labels = [];
        $dates = [];

        foreach ($period as $date) {
            $labels[] = $date->translatedFormat('D, d M');
            $dates[] = $date->toDateString();
        }

        $data = AbsensiModel::query()
            ->whereBetween('tanggal', [$startDate, $endDate], 'and', false)
            ->selectRaw('DATE(tanggal) as tanggal_absensi, status, COUNT(*) as total')
            ->groupByRaw('DATE(tanggal), status')
            ->get()
            ->groupBy('tanggal_absensi');

        $statuses = [
            'hadir' => [],
            'sakit' => [],
            'izin' => [],
            'alfa' => [],
            'terlambat' => [],
        ];

        foreach ($dates as $date) {
            $dailyData = $data->get($date, collect());

            foreach (array_keys($statuses) as $status) {
                $statuses[$status][] = (int) optional(
                    $dailyData->firstWhere('status', $status)
                )->total;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Hadir',
                    'data' => $statuses['hadir'],
                    'backgroundColor' => '#22c55e',
                    'borderColor' => '#16a34a',
                    'borderRadius' => 10,
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Sakit',
                    'data' => $statuses['sakit'],
                    'backgroundColor' => '#facc15',
                    'borderColor' => '#ca8a04',
                    'borderRadius' => 10,
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Izin',
                    'data' => $statuses['izin'],
                    'backgroundColor' => '#38bdf8',
                    'borderColor' => '#0284c7',
                    'borderRadius' => 10,
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Alfa',
                    'data' => $statuses['alfa'],
                    'backgroundColor' => '#ef4444',
                    'borderColor' => '#dc2626',
                    'borderRadius' => 10,
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Terlambat',
                    'data' => $statuses['terlambat'],
                    'backgroundColor' => '#fb923c',
                    'borderColor' => '#ea580c',
                    'borderRadius' => 10,
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,

            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20,
                    ],
                ],
                'tooltip' => [
                    'enabled' => true,
                    'backgroundColor' => '#111827',
                    'titleColor' => '#ffffff',
                    'bodyColor' => '#ffffff',
                    'padding' => 12,
                    'cornerRadius' => 8,
                ],
            ],

            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 12,
                        ],
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => '#e5e7eb',
                    ],
                    'ticks' => [
                        'precision' => 0,
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
