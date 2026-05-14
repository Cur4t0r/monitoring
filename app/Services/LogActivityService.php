<?php

namespace App\Services;

use App\Helpers\BandwidthFormatter;
use App\Models\LogActivity;
use App\Models\Opd;
use Carbon\Carbon;

/**
 * Service layer untuk encapsulate business logic LogActivity.
 */
class LogActivityService
{
    /**
     * Hitung stats detail untuk satu OPD dalam periode tertentu.
     * Mengembalikan array dengan nilai sudah terformat siap ditampilkan.
     */
    public function getDetailStats(Opd $opd): array
    {
        $aggregate = $opd->logActivities()
            ->selectRaw('
                MAX(in_bps)  AS max_in,
                AVG(in_bps)  AS avg_in,
                MAX(out_bps) AS max_out,
                AVG(out_bps) AS avg_out
            ')
            ->first();

        $latest = $opd->logActivities()->latest('timestamp')->first();

        // Format di service, bukan di Resource atau View
        return [
            'max_in'      => BandwidthFormatter::format((float) ($aggregate->max_in  ?? 0)),
            'avg_in'      => BandwidthFormatter::format((float) ($aggregate->avg_in  ?? 0)),
            'current_in'  => BandwidthFormatter::format((float) ($latest->in_bps     ?? 0)),
            'max_out'     => BandwidthFormatter::format((float) ($aggregate->max_out ?? 0)),
            'avg_out'     => BandwidthFormatter::format((float) ($aggregate->avg_out ?? 0)),
            'current_out' => BandwidthFormatter::format((float) ($latest->out_bps    ?? 0)),
        ];
    }

    /**
     * Hitung stats agregat untuk period tertentu (daily/weekly/monthly/yearly).
     * Mengembalikan array bps mentah (unformatted) — caller bisa format sesuai kebutuhan.
     */
    public function getAggregateStats(
        ?int $opdId,
        Carbon $from,
        ?Carbon $until = null
    ): array {
        $until ??= now();

        $agg = LogActivity::query()
            ->when($opdId, fn($q) => $q->where('opd_id', $opdId))
            ->whereBetween('timestamp', [$from, $until])
            ->selectRaw('
                MAX(in_bps)  AS max_in,
                AVG(in_bps)  AS avg_in,
                MAX(out_bps) AS max_out,
                AVG(out_bps) AS avg_out
            ')
            ->first();

        $latest = LogActivity::query()
            ->when($opdId, fn($q) => $q->where('opd_id', $opdId))
            ->whereBetween('timestamp', [$from, $until])
            ->latest('timestamp')
            ->first();

        // Kembalikan mentah (bps), caller yang format sesuai context
        return [
            'max_in'      => (float) ($agg->max_in  ?? 0),
            'avg_in'      => (float) ($agg->avg_in  ?? 0),
            'current_in'  => (float) ($latest->in_bps  ?? 0),
            'max_out'     => (float) ($agg->max_out ?? 0),
            'avg_out'     => (float) ($agg->avg_out ?? 0),
            'current_out' => (float) ($latest->out_bps ?? 0),
        ];
    }

    /**
     * Hitung stats agregat + format sekaligus.
     * Convenience method untuk use case yang perlu stat terformat langsung.
     */
    public function getFormattedStats(
        ?int $opdId,
        Carbon $from,
        ?Carbon $until = null
    ): array {
        $rawStats = $this->getAggregateStats($opdId, $from, $until);

        return [
            'max_in'      => BandwidthFormatter::format($rawStats['max_in']),
            'avg_in'      => BandwidthFormatter::format($rawStats['avg_in']),
            'current_in'  => BandwidthFormatter::format($rawStats['current_in']),
            'max_out'     => BandwidthFormatter::format($rawStats['max_out']),
            'avg_out'     => BandwidthFormatter::format($rawStats['avg_out']),
            'current_out' => BandwidthFormatter::format($rawStats['current_out']),
        ];
    }

    /**
     * Check status online/offline untuk satu OPD.
     * OPD dianggap online jika punya record dalam 10 menit terakhir.
     */
    public function isOnline(Opd $opd, int $thresholdMinutes = 10): bool
    {
        return $opd->logActivities()
            ->where('timestamp', '>=', now()->subMinutes($thresholdMinutes))
            ->exists();
    }

    /**
     * Hitung berapa OPD yang sedang online.
     */
    public function getOnlineCount(int $thresholdMinutes = 10): int
    {
        return LogActivity::query()
            ->where('timestamp', '>=', now()->subMinutes($thresholdMinutes))
            ->distinct('opd_id')
            ->count('opd_id');
    }
}
