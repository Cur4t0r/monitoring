<?php

namespace App\Filament\Resources\LogActivityResource\Widgets;

use App\Models\LogActivity;
use Carbon\Carbon;

class WeeklyBandwidthChart extends BaseBandwidthChart
{
    protected static ?string $heading = 'Mingguan';

    protected function getPeriodStart(): Carbon
    {
        return now()->subWeek();
    }

    protected function getData(): array
    {
        $from = $this->getPeriodStart();

        $data = LogActivity::query()
            ->when($this->opdId, fn($q) => $q->where('opd_id', $this->opdId))
            ->where('timestamp', '>=', $from)
            ->selectRaw("
                DATE(timestamp)                 AS sort_time,
                DATE_FORMAT(timestamp, '%d %b') AS label,
                AVG(in_bps)                     AS avg_in,
                AVG(out_bps)                    AS avg_out
            ")
            ->groupBy('sort_time', 'label')
            ->orderBy('sort_time')
            ->get();

        return $this->buildDataset($data, $from);
    }
}
