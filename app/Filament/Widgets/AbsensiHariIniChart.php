<?php

namespace App\Filament\Widgets;

use App\Models\AbsensiModel;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class AbsensiHariIniChart extends ChartWidget
{
    protected ?string $heading = 'Komposisi Absensi Hari Ini';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '30s';

    protected function getData(): array
    {
        $today = Carbon::today();

        $counts = AbsensiModel::query()
            ->whereDate('tanggal', '=', $today->toDateString(), 'and')
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $hadir = (int) $counts->get('hadir', 0);
        $sakit = (int) $counts->get('sakit', 0);
        $izin = (int) $counts->get('izin', 0);
        $alfa = (int) $counts->get('alfa', 0);
        $terlambat = (int) $counts->get('terlambat', 0);

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah',
                    'data' => [
                        $hadir,
                        $sakit,
                        $izin,
                        $alfa,
                        $terlambat,
                    ],
                    'backgroundColor' => [
                        '#22c55e',
                        '#facc15',
                        '#38bdf8',
                        '#ef4444',
                        '#fb923c',
                    ],
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                    'hoverOffset' => 8,
                ],
            ],
            'labels' => [
                'Hadir',
                'Sakit',
                'Izin',
                'Alfa',
                'Terlambat',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'cutout' => '65%',
        ];
    }
}
