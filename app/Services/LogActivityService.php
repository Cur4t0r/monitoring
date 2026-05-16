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
     * Raw SQL untuk select agregat stats (max/avg in/out) dalam satu query.
     */
    private const STATS_RAW = '
        MAX(in_bps)  AS max_in,
        AVG(in_bps)  AS avg_in,
        MAX(out_bps) AS max_out,
        AVG(out_bps) AS avg_out
    ';

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
     * Fetch data agregat (max/avg in/out) untuk OPD tertentu dalam periode waktu tertentu.
     */
    private function fetchAggregate(?int $opdId, ?Carbon $from = null, ?Carbon $until = null): object
    {
        return $this->baseQuery($opdId)
            ->when($from, fn($q) => $q->where('timestamp', '>=', $from))
            ->when($until, fn($q) => $q->where('timestamp', '<=', $until))
            ->selectRaw(self::STATS_RAW)
            ->first() ?? (object) []; // Pastikan selalu return object, meski tidak ada data
    }

    /**
     * Fetch record LogActivity terbaru untuk OPD tertentu, untuk dapatkan nilai current in/out.
     */
    private function fetchLatest(?int $opdId): ?LogActivity
    {
        return $this->baseQuery($opdId)->latest('timestamp')->first();
    }

    /**
     * Build array stats mentah (bps) dari hasil query agregat dan latest, dengan handling null/default.
     */
    private function buildRawStats(?object $aggregate, ?object $latest): array
    {
        return [
            'max_in'      => (float) ($aggregate?->max_in  ?? 0),
            'avg_in'      => (float) ($aggregate?->avg_in  ?? 0),
            'current_in'  => (float) ($latest?->in_bps     ?? 0),
            'max_out'     => (float) ($aggregate?->max_out ?? 0),
            'avg_out'     => (float) ($aggregate?->avg_out ?? 0),
            'current_out' => (float) ($latest?->out_bps    ?? 0),
        ];
    }

    /**
     * Format array stats menjadi unit yang lebih mudah dibaca (Kbps/Mbps).
     */
    private function formatStatsArray(array $rawStats): array
    {
        return array_map(
            fn(float $bps) => BandwidthFormatter::format($bps),
            $rawStats
        );
    }

    /**
     * Public method untuk dipanggil dari luar service, mengembalikan stats agregat dalam format mentah (bps).
     */
    public function getAggregateStats(?int $opdId, ?Carbon $from = null, ?Carbon $until = null): array
    {
        return $this->buildRawStats(
            $this->fetchAggregate($opdId, $from, $until),
            $this->fetchLatest($opdId)
        );
    }

    /**
     * Public method untuk dipanggil dari luar service, mengembalikan stats agregat dalam format terformat (Kbps/Mbps).
     */
    public function getFormattedStats(?int $opdId, ?Carbon $from = null, ?Carbon $until = null): array
    {
        return $this->formatStatsArray(
            $this->getAggregateStats($opdId, $from, $until)
        );
    }

    /**
     * Public method untuk fetch stats detail (max/avg/current in/out) untuk OPD tertentu.
     */
    public function getDetailStats(Opd $opd): array
    {
        return $this->getFormattedStats($opd->id, null, null);
    }

    /**
     * Public method untuk fetch stats agregat untuk semua OPD dalam periode waktu tertentu, dengan format terformat (Kbps/Mbps) dan tambahan nama OPD.
     * Digunakan untuk export Excel, sehingga return Collection dengan key = opd_id dan value = array stats + nama_opd.
     * Implementasi efisien dengan hanya 2 query ke DB (satu untuk agregat, satu untuk latest), lalu gabungkan di PHP.
     * Hasilnya adalah Collection seperti: [opd_id => ['nama_opd' => '...', 'max_in' => '...', 'avg_in' => '...', ...], ...]
     * Jika $from = null, maka fetch untuk semua data tanpa filter waktu.
     * Jika $from diberikan, maka fetch hanya untuk data sejak $from hingga sekarang.
     */
    public function getAllOpdsStats(?Carbon $from = null): Collection
    {
        // Query pertama untuk fetch agregat max/avg in/out per OPD dalam periode tertentu
        $aggregates = LogActivity::query()
            ->when($from, fn($q) => $q->where('timestamp', '>=', $from))
            ->selectRaw('opd_id, ' . self::STATS_RAW)
            ->groupBy('opd_id')
            ->get()
            ->keyBy('opd_id');

        // Satu query latest untuk semua OPD yang punya record dalam periode tersebut, untuk dapatkan nilai current in/out
        $latestIds = LogActivity::query()
            ->when($from, fn($q) => $q->where('timestamp', '>=', $from))
            ->selectRaw('MAX(id) AS id, opd_id')
            ->groupBy('opd_id')
            ->pluck('id');

        // Query untuk fetch record LogActivity terbaru per OPD berdasarkan id yang sudah didapatkan di atas, lalu keyBy opd_id untuk memudahkan penggabungan dengan data agregat
        $latests = LogActivity::query()
            ->whereIn('id', $latestIds)
            ->get()
            ->keyBy('opd_id');

        // Gabungkan data agregat dan latest dengan daftar OPD, lalu format hasilnya menjadi array stats terformat + nama_opd
        return Opd::orderBy('nama_opd')->get()->mapWithKeys(function (Opd $opd) use ($aggregates, $latests) {
            $agg    = $aggregates->get($opd->id);
            $latest = $latests->get($opd->id);

            // Re-use logic pembentukan array dan formatter
            $rawStats       = $this->buildRawStats($agg, $latest);
            $formattedStats = $this->formatStatsArray($rawStats);

            // Tambahkan nama OPD setelah di-format agar bisa langsung digunakan untuk export atau tampilan
            $formattedStats['nama_opd'] = $opd->nama_opd;

            return [$opd->id => $formattedStats];
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
