<?php

namespace App\Filament\Resources\LogActivityResource\Widgets;

use App\Helpers\BandwidthFormatter;
use App\Models\LogActivity;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;

abstract class BaseBandwidthChart extends ChartWidget
{
    public ?int $opdId = null;

    protected array $stats = [];

    protected int | string | array $columnSpan = 1;

    abstract protected function getPeriodStart(): Carbon;

    // Listener event OPD filter dari dropdown di header=
    #[On('opdFilterUpdated')]
    public function updateOpdFilter(?int $opdId): void
    {
        $this->opdId = $opdId;
    }

    // Hitung stats Max / Avg / Current untuk periode aktif
    protected function computeStats(Carbon $from): array
    {
        $agg = LogActivity::query()
            ->when($this->opdId, fn($q) => $q->where('opd_id', $this->opdId))
            ->where('timestamp', '>=', $from)
            ->selectRaw('
                MAX(in_bps)  AS max_in,
                AVG(in_bps)  AS avg_in,
                MAX(out_bps) AS max_out,
                AVG(out_bps) AS avg_out
            ')
            ->first();

        $latest = LogActivity::query()
            ->when($this->opdId, fn($q) => $q->where('opd_id', $this->opdId))
            ->latest('timestamp')
            ->first();

        return [
            'max_in'      => BandwidthFormatter::format((float) ($agg->max_in  ?? 0)),
            'avg_in'      => BandwidthFormatter::format((float) ($agg->avg_in  ?? 0)),
            'current_in'  => BandwidthFormatter::format((float) ($latest->in_bps  ?? 0)),
            'max_out'     => BandwidthFormatter::format((float) ($agg->max_out ?? 0)),
            'avg_out'     => BandwidthFormatter::format((float) ($agg->avg_out ?? 0)),
            'current_out' => BandwidthFormatter::format((float) ($latest->out_bps ?? 0)),
        ];
    }

    // -------------------------------------------------------------------------
    // Footer — Max / Avg / Current di bawah canvas
    // -------------------------------------------------------------------------

    public function getFooter(): HtmlString
    {
        $s = $this->stats ?: $this->computeStats($this->getPeriodStart());

        $row = fn(string $dir, string $color): string =>
        <<<HTML
            <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs">
                <span>
                    <span class="font-semibold" style="color:{$color}">Max {$dir}:</span>
                    <span class="text-gray-300">{$s['max_' . strtolower($dir)]}</span>
                </span>
                <span>
                    <span class="font-semibold" style="color:{$color}">Average {$dir}:</span>
                    <span class="text-gray-300">{$s['avg_' . strtolower($dir)]}</span>
                </span>
                <span>
                    <span class="font-semibold" style="color:{$color}">Current {$dir}:</span>
                    <span class="text-gray-300">{$s['current_' . strtolower($dir)]}</span>
                </span>
            </div>
            HTML;

        return new HtmlString(<<<HTML
        <div class="px-4 pb-4 pt-1 space-y-1 border-t border-white/10 mt-2">
            {$row('In',  '#3b82f6')}
            {$row('Out', '#22c55e')}
        </div>
        HTML);
    }

    // Build dataset + isi $this->stats sebagai side-effect
    protected function buildDataset(iterable $rows, Carbon $from): array
    {
        $this->stats = $this->computeStats($from);

        $col = collect($rows);

        return [
            'datasets' => [
                [
                    'label'           => 'Inbound (Mbps)',
                    'data'            => $col->map(fn($r) => round(($r->avg_in  ?? 0) / 1_000_000, 2))->toArray(),
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
            'interaction'         => ['mode' => 'index', 'intersect' => false],
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
