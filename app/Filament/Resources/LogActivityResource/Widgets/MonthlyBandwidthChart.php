<?php

namespace App\Filament\Resources\LogActivityResource\Widgets;

use App\Models\LogActivity;
// use Filament\Widgets\ChartWidget;

class MonthlyBandwidthChart extends BaseBandwidthChart
{
    protected static ?string $heading = 'Bulanan';

    protected function getData(): array
    {
        $data = LogActivity::query()
            ->when($this->opdId, fn($q) => $q->where('opd_id', $this->opdId))
            ->where('timestamp', '>=', now()->subMonth())
            ->selectRaw("
                DATE(timestamp)                 AS sort_time,
                DATE_FORMAT(timestamp, '%d %b') AS label,
                AVG(in_bps)                     AS avg_in,
                AVG(out_bps)                    AS avg_out
            ")
            ->groupBy('sort_time', 'label')
            ->orderBy('sort_time')
            ->get();

        return $this->buildDataset($data);

        // return [
        //     //
        // ];
    }

    // protected function getType(): string
    // {
    //     return 'line';
    // }
}
