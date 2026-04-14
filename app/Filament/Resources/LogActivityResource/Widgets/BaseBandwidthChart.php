<?php

namespace App\Filament\Resources\LogActivityResource\Widgets;

use Filament\Widgets\ChartWidget;
use Livewire\Attributes\On;

abstract class BaseBandwidthChart extends ChartWidget
{
    // protected static ?string $heading = 'Chart';

    public ?int $opdId = null;

    /**
     * Setiap chart mengambil setengah lebar halaman (2 kolom → grid 2×2).
     */
    protected int | string | array $columnSpan = 1;
 
    // protected function getData(): array
    // {
    //     return [
    //         //
    //     ];
    // }

    // -------------------------------------------------------------------------
    // Listener event Livewire
    // -------------------------------------------------------------------------

    /**
     * Dipanggil ketika user memilih OPD di header halaman.
     * Livewire otomatis trigger re-render → getData() dipanggil ulang.
     */
    #[On('opdFilterUpdated')]
    public function updateOpdFilter(?int $opdId): void
    {
        $this->opdId = $opdId;
    }

    // -------------------------------------------------------------------------
    // Helper — membangun format datasets untuk Chart.js
    // -------------------------------------------------------------------------

    protected function buildDataset(iterable $rows): array
    {
        $col = collect($rows);

        return [
            'datasets' => [
                [
                    'label'           => 'Inbound (Mbps)',
                    'data'            => $col->map(fn($r) => round(($r->avg_in ?? 0) / 1_000_000, 2))->toArray(),
                    'borderColor'     => '#3b82f6',
                    'backgroundColor' => 'rgba(59,130,246,0.08)',
                    'fill'            => true,
                    'tension'         => 0.4,
                    'pointRadius'     => 2,
                    'borderWidth'     => 2,
                ],
                [
                    'label'           => 'Outbound (Mbps)',
                    'data'            => $col->map(fn($r) => round(($r->avg_out ?? 0) / 1_000_000, 2))->toArray(),
                    'borderColor'     => '#22c55e',
                    'backgroundColor' => 'rgba(34,197,94,0.08)',
                    'fill'            => true,
                    'tension'         => 0.4,
                    'pointRadius'     => 2,
                    'borderWidth'     => 2,
                ],
            ],
            'labels' => $col->pluck('label')->toArray(),
        ];
    }


    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive'          => true,
            'maintainAspectRatio' => false,
            'interaction'         => [
                'mode'      => 'index',
                'intersect' => false,
            ],
            'plugins' => [
                'legend'  => ['display' => true, 'position' => 'top'],
                'tooltip' => ['mode' => 'index', 'intersect' => false],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title'       => ['display' => true, 'text' => 'Mbps'],
                    'grid'        => ['color' => 'rgba(156,163,175,0.1)'],
                ],
                'x' => [
                    'title' => ['display' => true, 'text' => 'Waktu'],
                    'ticks' => ['maxTicksLimit' => 12],
                    'grid'  => ['display' => false],
                ],
            ],
        ];
    }
}
