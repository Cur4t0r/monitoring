<?php

namespace App\Filament\Resources\LogActivityResource\Widgets;

use App\Models\LogActivity;
use App\Models\Opd;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Widgets\ChartWidget;
// use Illuminate\Support\Facades\DB;

class BandwidthTrafficChart extends ChartWidget
{
    protected static ?string $heading = 'Traffic Bandwidth';

    protected int | string | array $columnSpan = 'full';

    public ?string $filter = 'daily';

    public ?int $opdId = null;

    protected function getFilters(): ?array
    {
        return [
            'daily' => 'Harian',
            'weekly' => 'Mingguan',
            'monthly' => 'Bulanan',
            'yearly' => 'Tahunan',
        ];
    }

    protected function getHeaderActions(): array
    {
        // Label tombol menampilkan nama OPD yang sedang aktif
        $label = $this->opdId
            ? (Opd::find($this->opdId)?->nama_opd ?? 'OPD')
            : 'Semua OPD';

        return [
            Action::make('filterOpd')
                ->label($label)
                ->icon('heroicon-o-building-office-2')
                ->color('gray')
                ->form([
                    Select::make('opd_id')
                        ->label('Pilih OPD')
                        ->options(
                            Opd::orderBy('nama_opd')->pluck('nama_opd', 'id')
                        )
                        ->placeholder('Semua OPD')
                        ->default($this->opdId)
                        ->nullable(),
                ])
                ->action(function (array $data): void {
                    // Update property Livewire → Filament otomatis re-render chart
                    $this->opdId = $data['opd_id'] ? (int) $data['opd_id'] : null;
                }),
        ];
    }

    protected function getData(): array
    {
        $query = LogActivity::query();

        // if ($this->filter === 'daily') {
        //     $query->where('timestamp', '>=', now()->subDay());

        //     $data = $query
        //         ->selectRaw("
        //             DATE_FORMAT(timestamp, '%H:00') as label,
        //             AVG(in_bps) as avg_in,
        //             AVG(out_bps) as avg_out,
        //             DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00') as sort_time
        //         ")
        //         ->groupBy('label', 'sort_time')
        //         ->orderBy('sort_time')
        //         ->get();
        // } elseif ($this->filter === 'weekly') {
        //     $query->where('timestamp', '>=', now()->subWeek());

        //     $data = $query
        //         ->selectRaw("
        //             DATE(timestamp) as sort_time,
        //             DATE_FORMAT(timestamp, '%d %b') as label,
        //             AVG(in_bps) as avg_in,
        //             AVG(out_bps) as avg_out
        //         ")
        //         ->groupBy('sort_time', 'label')
        //         ->orderBy('sort_time')
        //         ->get();
        // } elseif ($this->filter === 'monthly') {
        //     $query->where('timestamp', '>=', now()->subMonth());

        //     $data = $query
        //         ->selectRaw("
        //             DATE(timestamp) as sort_time,
        //             DATE_FORMAT(timestamp, '%d %b') as label,
        //             AVG(in_bps) as avg_in,
        //             AVG(out_bps) as avg_out
        //         ")
        //         ->groupBy('sort_time', 'label')
        //         ->orderBy('sort_time')
        //         ->get();
        // } else {
        //     $query->where('timestamp', '>=', now()->subYear());

        //     $data = $query
        //         ->selectRaw("
        //             DATE_FORMAT(timestamp, '%Y-%m') as sort_time,
        //             DATE_FORMAT(timestamp, '%b %Y') as label,
        //             AVG(in_bps) as avg_in,
        //             AVG(out_bps) as avg_out
        //         ")
        //         ->groupBy('sort_time', 'label')
        //         ->orderBy('sort_time')
        //         ->get();
        // }

        // return [
        //     'datasets' => [
        //         [
        //             'label' => 'Inbound (Mbps)',
        //             'data' => $data->map(fn($item) => round(($item->avg_in ?? 0) / 1_000_000, 2))->toArray(),
        //             'borderColor' => '#3b82f6',
        //             'backgroundColor' => 'rgba(59,130,246,0.1)',
        //             'tension' => 0.3,
        //         ],
        //         [
        //             'label' => 'Outbound (Mbps)',
        //             'data' => $data->map(fn($item) => round(($item->avg_out ?? 0) / 1_000_000, 2))->toArray(),
        //             'borderColor' => '#22c55e',
        //             'backgroundColor' => 'rgba(34,197,94,0.1)',
        //             'tension' => 0.3,
        //         ],
        //     ],
        //     'labels' => $data->pluck('label')->toArray(),
        // ];

        // Terapkan filter OPD jika dipilih
        if ($this->opdId) {
            $query->where('opd_id', $this->opdId);
        }

        $data = match ($this->filter) {

            // ------------------------------------------------------------------
            // HARIAN — data setiap 5 menit, ditampilkan rata-rata per JAM
            // sort_time harus menyertakan tanggal agar urutan tidak kacau
            // ------------------------------------------------------------------
            'daily' => $query
                ->where('timestamp', '>=', now()->subDay())
                ->selectRaw("
                    DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00') AS sort_time,
                    DATE_FORMAT(timestamp, '%H:00')             AS label,
                    AVG(in_bps)                                 AS avg_in,
                    AVG(out_bps)                                AS avg_out
                ")
                ->groupBy('sort_time', 'label')
                ->orderBy('sort_time')
                ->get(),

            // ------------------------------------------------------------------
            // MINGGUAN — rata-rata per HARI selama 7 hari terakhir
            // ------------------------------------------------------------------
            'weekly' => $query
                ->where('timestamp', '>=', now()->subWeek())
                ->selectRaw("
                    DATE(timestamp)                          AS sort_time,
                    DATE_FORMAT(timestamp, '%d %b')          AS label,
                    AVG(in_bps)                              AS avg_in,
                    AVG(out_bps)                             AS avg_out
                ")
                ->groupBy('sort_time', 'label')
                ->orderBy('sort_time')
                ->get(),

            // ------------------------------------------------------------------
            // BULANAN — rata-rata per HARI selama 30 hari terakhir
            // ------------------------------------------------------------------
            'monthly' => $query
                ->where('timestamp', '>=', now()->subMonth())
                ->selectRaw("
                    DATE(timestamp)                          AS sort_time,
                    DATE_FORMAT(timestamp, '%d %b')          AS label,
                    AVG(in_bps)                              AS avg_in,
                    AVG(out_bps)                             AS avg_out
                ")
                ->groupBy('sort_time', 'label')
                ->orderBy('sort_time')
                ->get(),

            // ------------------------------------------------------------------
            // TAHUNAN — rata-rata per BULAN selama 12 bulan terakhir
            // ------------------------------------------------------------------
            default => $query
                ->where('timestamp', '>=', now()->subYear())
                ->selectRaw("
                    DATE_FORMAT(timestamp, '%Y-%m')          AS sort_time,
                    DATE_FORMAT(timestamp, '%b %Y')          AS label,
                    AVG(in_bps)                              AS avg_in,
                    AVG(out_bps)                             AS avg_out
                ")
                ->groupBy('sort_time', 'label')
                ->orderBy('sort_time')
                ->get(),
        };

        return [
            'datasets' => [
                [
                    'label'           => 'Inbound (Mbps)',
                    'data'            => $data->map(
                        fn($item) => round(($item->avg_in ?? 0) / 1_000_000, 2)
                    )->toArray(),
                    'borderColor'     => '#3b82f6',
                    'backgroundColor' => 'rgba(59,130,246,0.08)',
                    'fill'            => true,
                    'tension'         => 0.4,
                    'pointRadius'     => 2,
                ],
                [
                    'label'           => 'Outbound (Mbps)',
                    'data'            => $data->map(
                        fn($item) => round(($item->avg_out ?? 0) / 1_000_000, 2)
                    )->toArray(),
                    'borderColor'     => '#22c55e',
                    'backgroundColor' => 'rgba(34,197,94,0.08)',
                    'fill'            => true,
                    'tension'         => 0.4,
                    'pointRadius'     => 2,
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
            'interaction'         => [
                'mode'      => 'index',   // tooltip muncul untuk semua dataset sekaligus
                'intersect' => false,
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Mbps',
                    ],
                    'grid'        => [
                        'color' => 'rgba(255,255,255,0.05)'
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Waktu',
                    ],
                    'ticks'   => [
                        'maxTicksLimit' => 24
                    ],   // max 24 label agar tidak penuh
                    'grid'    => [
                        'display' => false
                    ],
                ],
            ],
        ];
    }
}
