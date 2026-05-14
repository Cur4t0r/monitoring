<?php

namespace App\Services;

use App\Helpers\BandwidthFormatter;
use App\Models\LogActivity;
use App\Models\Opd;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Service layer untuk encapsulate business logic LogActivity.
 */
class LogActivityService
{
    /**
     * Threshold waktu untuk menentukan status online/offline OPD (dalam menit).
     * OPD dianggap online jika punya record dalam X menit terakhir.
     */
    private const ONLINE_THRESHOLD_MINUTES = 10;

    public const BPS_MIN = 300_000;
    public const BPS_MAX = 40_000_000;

    /**
     * Base query builder untuk LogActivity, dengan optional filter OPD.
     * Digunakan sebagai dasar untuk berbagai query stats di service ini.
     */
    private function baseQuery(?int $opdId): Builder
    {
        return LogActivity::query()
            ->when($opdId, fn($q) => $q->where('opd_id', $opdId));
    }

    /**
     * Hitung stats agregat untuk periode tertentu (daily/weekly/monthly/yearly).
     * Mengembalikan object dengan nilai mentah (bps), caller bisa format sesuai kebutuhan
     */
    private function fetchAggregate(?int $opdId, Carbon $from, ?Carbon $until = null): object
    {
        return $this->baseQuery($opdId)
            ->whereBetween('timestamp', [$from, $until ?? now()])
            ->selectRaw('
                MAX(in_bps)  AS max_in,
                AVG(in_bps)  AS avg_in,
                MAX(out_bps) AS max_out,
                AVG(out_bps) AS avg_out
            ')
            ->first();
    }

    /**
     * Fetch record LogActivity terbaru untuk OPD tertentu (atau global jika OPD null).
     */
    private function fetchLatest(?int $opdId): ?LogActivity
    {
        return $this->baseQuery($opdId)->latest('timestamp')->first();
    }

    /**
     * Hitung stats agregat untuk periode tertentu, dengan opsi format langsung.
     * Jika $formatted = true, kembalikan nilai sudah terformat siap ditampilkan
     */
    private function buildRawStats(object $aggregate, ?LogActivity $latest): array
    {
        return [
            'max_in'      => (float) ($aggregate->max_in  ?? 0),
            'avg_in'      => (float) ($aggregate->avg_in  ?? 0),
            'current_in'  => (float) ($latest->in_bps     ?? 0),
            'max_out'     => (float) ($aggregate->max_out ?? 0),
            'avg_out'     => (float) ($aggregate->avg_out ?? 0),
            'current_out' => (float) ($latest->out_bps    ?? 0),
        ];
    }

    /**
     * Public method untuk dipanggil dari luar service, mengembalikan stats agregat dalam format mentah (bps).
     */
    public function getAggregateStats(?int $opdId, Carbon $from, ?Carbon $until = null): array
    {
        return $this->buildRawStats(
            $this->fetchAggregate($opdId, $from, $until),
            $this->fetchLatest($opdId)
        );
    }

    /**
     * Public method untuk dipanggil dari luar service, mengembalikan stats agregat dalam format siap tampil (Kbps/Mbps).
     */
    public function getFormattedStats(?int $opdId, Carbon $from, ?Carbon $until = null): array
    {
        return array_map(
            fn(float $bps) => BandwidthFormatter::format($bps),
            $this->getAggregateStats($opdId, $from, $until)
        );
    }

    /**
     * Public method untuk fetch stats detail (max/avg/current in/out) untuk OPD tertentu, dengan periode dari awal log sampai sekarang.
     */
    public function getDetailStats(Opd $opd): array
    {
        // Periode dari awal log OPD ini sampai sekarang
        return $this->getFormattedStats(
            opdId: $opd->id,
            from: Carbon::parse(
                $opd->logActivities()->min('timestamp') ?? now()->subYear()
            ),
        );
    }

    /**
     * Public method untuk fetch stats agregat untuk semua OPD dalam periode tertentu, dengan opsi format langsung.
     */
    public function getAllOpdsStats(Carbon $from): Collection
    {
        // Satu query aggregate untuk semua OPD
        $aggregates = LogActivity::query()
            ->where('timestamp', '>=', $from)
            ->selectRaw('
                opd_id,
                MAX(in_bps)  AS max_in,
                AVG(in_bps)  AS avg_in,
                MAX(out_bps) AS max_out,
                AVG(out_bps) AS avg_out
            ')
            ->groupBy('opd_id')
            ->get()
            ->keyBy('opd_id');

        // Satu query latest untuk semua OPD
        $latestIds = LogActivity::query()
            ->selectRaw('MAX(id) AS id, opd_id')
            ->groupBy('opd_id')
            ->pluck('id');

        $latests = LogActivity::query()
            ->whereIn('id', $latestIds)
            ->get()
            ->keyBy('opd_id');

        // Gabungkan dan format per OPD
        return Opd::orderBy('nama_opd')->get()->mapWithKeys(function (Opd $opd) use ($aggregates, $latests) {
            $agg    = $aggregates->get($opd->id);
            $latest = $latests->get($opd->id);

            return [
                $opd->id => [
                    'nama_opd'    => $opd->nama_opd,
                    'max_in'      => BandwidthFormatter::format((float) ($agg->max_in   ?? 0)),
                    'avg_in'      => BandwidthFormatter::format((float) ($agg->avg_in   ?? 0)),
                    'current_in'  => BandwidthFormatter::format((float) ($latest->in_bps  ?? 0)),
                    'max_out'     => BandwidthFormatter::format((float) ($agg->max_out  ?? 0)),
                    'avg_out'     => BandwidthFormatter::format((float) ($agg->avg_out  ?? 0)),
                    'current_out' => BandwidthFormatter::format((float) ($latest->out_bps ?? 0)),
                ],
            ];
        });
    }

    /**
     * Tentukan apakah OPD sedang online atau offline berdasarkan ada tidaknya record dalam threshold waktu tertentu.
     */
    public function isOnline(Opd $opd): bool
    {
        return $this->baseQuery($opd->id)
            ->where('timestamp', '>=', now()->subMinutes(self::ONLINE_THRESHOLD_MINUTES))
            ->exists();
    }

    /**
     * Hitung jumlah OPD yang sedang online (memiliki record dalam threshold waktu tertentu).
     */
    public function getOnlineCount(): int
    {
        return LogActivity::query()
            ->where('timestamp', '>=', now()->subMinutes(self::ONLINE_THRESHOLD_MINUTES))
            ->distinct('opd_id')
            ->count('opd_id');
    }

    /**
     * Fetch data untuk sparkline jumlah OPD yang sedang online dalam periode tertentu.
     */
    public function getOnlineSparkline(int $days = 7): array
    {
        return collect(range($days - 1, 0))
            ->map(function (int $daysAgo) {
                $date = now()->subDays($daysAgo)->toDateString();

                return LogActivity::query()
                    ->whereBetween('timestamp', [
                        $date . ' 00:00:00',
                        $date . ' 23:59:59',
                    ])
                    ->distinct('opd_id')
                    ->count('opd_id');
            })
            ->values()
            ->toArray();
    }
}
