<?php

namespace App\Filament\Resources\LogActivityResource\Widgets;

use App\Models\LogActivity;
use Filament\Widgets\ChartWidget;
// use Illuminate\Support\Facades\DB;

class BandwidthTrafficChart extends ChartWidget
{
    protected static ?string $heading = 'Traffic Bandwidth';

    protected int | string | array $columnSpan = 'full';

    public ?string $filter = 'daily';

    protected function getFilters(): ?array
    {
        return [
            'daily' => 'Harian',
            'weekly' => 'Mingguan',
            'monthly' => 'Bulanan',
            'yearly' => 'Tahunan',
        ];
    }

    protected function getData(): array
    {
        $query = LogActivity::query();

        if ($this->filter === 'daily') {
            $query->where('timestamp', '>=', now()->subDay());

            $data = $query
                ->selectRaw("
                    DATE_FORMAT(timestamp, '%H:00') as label,
                    AVG(in_bps) as avg_in,
                    AVG(out_bps) as avg_out,
                    DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00') as sort_time
                ")
                ->groupBy('label', 'sort_time')
                ->orderBy('sort_time')
                ->get();
        } elseif ($this->filter === 'weekly') {
            $query->where('timestamp', '>=', now()->subWeek());

            $data = $query
                ->selectRaw("
                    DATE(timestamp) as sort_time,
                    DATE_FORMAT(timestamp, '%d %b') as label,
                    AVG(in_bps) as avg_in,
                    AVG(out_bps) as avg_out
                ")
                ->groupBy('sort_time', 'label')
                ->orderBy('sort_time')
                ->get();
        } elseif ($this->filter === 'monthly') {
            $query->where('timestamp', '>=', now()->subMonth());

            $data = $query
                ->selectRaw("
                    DATE(timestamp) as sort_time,
                    DATE_FORMAT(timestamp, '%d %b') as label,
                    AVG(in_bps) as avg_in,
                    AVG(out_bps) as avg_out
                ")
                ->groupBy('sort_time', 'label')
                ->orderBy('sort_time')
                ->get();
        } else {
            $query->where('timestamp', '>=', now()->subYear());

            $data = $query
                ->selectRaw("
                    DATE_FORMAT(timestamp, '%Y-%m') as sort_time,
                    DATE_FORMAT(timestamp, '%b %Y') as label,
                    AVG(in_bps) as avg_in,
                    AVG(out_bps) as avg_out
                ")
                ->groupBy('sort_time', 'label')
                ->orderBy('sort_time')
                ->get();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Inbound (Mbps)',
                    'data' => $data->map(fn($item) => round(($item->avg_in ?? 0) / 1_000_000, 2))->toArray(),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59,130,246,0.1)',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Outbound (Mbps)',
                    'data' => $data->map(fn($item) => round(($item->avg_out ?? 0) / 1_000_000, 2))->toArray(),
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34,197,94,0.1)',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $data->pluck('label')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Mbps',
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Waktu',
                    ],
                ],
            ],
        ];
    }
}
