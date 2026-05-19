<?php

namespace App\Filament\Widgets;

use App\Models\SystemAlert;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\HtmlString;

class SystemAlertChartWidget extends ChartWidget
{
    protected static ?string $heading = 'System Alerts';

    protected static ?string $pollingInterval = '30s';

    // Ambil 2 kolom dari grid 3 agar sejajar dengan UpcomingMaintenanceWidget (1 kolom)
    protected int | string | array $columnSpan = [
        'default' => 1,
        'md'      => 2,
        'xl'      => 2,
    ];

    protected static ?int $sort = 3;

    // Data chart — doughnut berdasarkan severity alert yang aktif
    protected function getData(): array
    {
        $critical     = SystemAlert::where('severity', 'critical')->where('status', 'active')->count();
        $warning      = SystemAlert::where('severity', 'warning')->where('status', 'active')->count();
        $info         = SystemAlert::where('severity', 'info')->where('status', 'active')->count();
        $acknowledged = SystemAlert::where('status', 'acknowledged')->count();
        $resolved     = SystemAlert::where('status', 'resolved')->count();

        return [
            'datasets' => [
                [
                    'data'            => [$critical, $warning, $info, $acknowledged, $resolved],
                    'backgroundColor' => [
                        'rgba(239, 68, 68, 0.85)',    // critical — merah
                        'rgba(245, 158, 11, 0.85)',   // warning  — kuning
                        'rgba(59, 130, 246, 0.85)',   // info     — biru
                        'rgba(168, 85, 247, 0.85)',   // acknowledged — ungu
                        'rgba(34, 197, 94, 0.85)',    // resolved — hijau
                    ],
                    'borderColor'     => 'transparent',
                    'borderWidth'     => 0,
                    'hoverOffset'     => 6,
                ],
            ],
            'labels' => [
                'Critical',
                'Warning',
                'Info',
                'Diakui',
                'Selesai',
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
            'responsive'          => true,
            'maintainAspectRatio' => false,
            'cutout'              => '40%',
            'plugins' => [
                'legend' => [
                    'display'  => true,
                    'position' => 'bottom',
                    'labels'   => [
                        'padding'   => 16,
                        'usePointStyle' => true,
                        'pointStyleWidth' => 8,
                    ],
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = total > 0 ? Math.round(context.parsed / total * 100) : 0;
                            return " " + context.label + ": " + context.parsed + " (" + pct + "%)";
                        }',
                    ],
                ],
            ],
            'scales' => [
                'x' => ['display' => false],
                'y' => ['display' => false],
            ],
        ];
    }

    // Footer — ringkasan angka key metrics
    public function getFooter(): HtmlString
    {
        $totalActive  = SystemAlert::where('status', 'active')->count();
        $critical     = SystemAlert::where('severity', 'critical')->where('status', 'active')->count();
        $warning      = SystemAlert::where('severity', 'warning')->where('status', 'active')->count();
        $resolvedToday = SystemAlert::where('status', 'resolved')
            ->whereDate('updated_at', today())
            ->count();

        $stat = fn(string $label, int|string $value, string $color): string =>
        <<<HTML
            <div class="flex flex-col items-center gap-0.5">
                <span class="text-lg font-bold" style="color:{$color}">{$value}</span>
                <span class="text-[10px] uppercase tracking-wide text-gray-400">{$label}</span>
            </div>
            HTML;

        return new HtmlString(<<<HTML
        <div class="flex justify-around px-4 pb-4 pt-2 border-t border-white/10 mt-1">
            {$stat('Total Aktif',$totalActive, '#f8fafc')}
            {$stat('Critical',$critical,    '#ef4444')}
            {$stat('Warning',$warning,     '#f59e0b')}
            {$stat('Resolved Hari Ini',$resolvedToday, '#22c55e')}
        </div>
        HTML);
    }
}
