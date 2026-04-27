<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Opd;
use Carbon\Carbon;

/**
 * Seeder dengan pola variasi realistis sehingga grafik naik-turun seperti data nyata:
 *
 *  - JAM SIBUK     : 08:00–17:00 bandwidth tinggi, malam rendah
 *  - HARI KERJA    : Senin–Jumat lebih tinggi dari Sabtu–Minggu
 *  - VARIASI MUSIM : Random multiplier per bulan (simulasi event/proyek besar)
 *  - NOISE         : ±30% random agar tidak terlihat terlalu pola
 *
 * Data harian (24 jam terakhir)
 * diisi oleh scheduler log:generate setiap 5 menit.
 */
class LogActivitySeeder extends Seeder
{
    // Base bandwidth per OPD — berbeda-beda agar tiap OPD punya "karakter" sendiri
    // Key = indeks OPD (0-based), Value = base bps
    private array $opdBaseMap = [];

    public function run(): void
    {
        DB::table('log_activities')->truncate();

        $now  = now();
        $opds = Opd::orderBy('id')->get(['id', 'nama_opd']);

        // Assign base bandwidth berbeda per OPD: 2 Mbps – 35 Mbps
        foreach ($opds as $i => $opd) {
            // Spread merata agar ada OPD "kecil" dan "besar"
            $this->opdBaseMap[$opd->id] = 2_000_000 + ($i * (33_000_000 / max(1, $opds->count() - 1)));
        }

        $this->command->getOutput()->progressStart($opds->count());

        foreach ($opds as $opd) {
            $rows = [];
            $base = $this->opdBaseMap[$opd->id];

            // ------------------------------------------------------------------
            // Segmen 1: 24 jam terakhir → setiap 5 menit (chart HARIAN)
            // ------------------------------------------------------------------
            $this->fillSegment(
                $rows,
                $opd->id,
                $base,
                from: $now->copy()->subDay(),
                until: $now,
                interval: 5
            );

            // ------------------------------------------------------------------
            // Segmen 2: 24 jam – 7 hari lalu → setiap 30 menit (chart MINGGUAN)
            // ------------------------------------------------------------------
            $this->fillSegment(
                $rows,
                $opd->id,
                $base,
                from: $now->copy()->subDays(7),
                until: $now->copy()->subDay(),
                interval: 30
            );

            // ------------------------------------------------------------------
            // Segmen 3: 7 hari – 30 hari lalu → setiap 1 jam (chart BULANAN)
            // ------------------------------------------------------------------
            $this->fillSegment(
                $rows,
                $opd->id,
                $base,
                from: $now->copy()->subDays(30),
                until: $now->copy()->subDays(7),
                interval: 60
            );

            // ------------------------------------------------------------------
            // Segmen 4: 30 hari – 1 tahun lalu → setiap 6 jam (chart TAHUNAN)
            // ------------------------------------------------------------------
            $this->fillSegment(
                $rows,
                $opd->id,
                $base,
                from: $now->copy()->subYear(),
                until: $now->copy()->subDays(30),
                interval: 360
            );

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('log_activities')->insert($chunk);
            }

            $this->command->getOutput()->progressAdvance();
        }

        $this->command->getOutput()->progressFinish();
        $this->command->info('Seeder selesai — semua chart langsung terisi.');
        $this->command->line('Jalankan scheduler agar data terus bertambah setiap 5 menit:');
        $this->command->line('php artisan schedule:work');
    }

    // -------------------------------------------------------------------------
    // Fill segment dengan pola realistis
    // -------------------------------------------------------------------------

    private function fillSegment(
        array  &$rows,
        int    $opdId,
        float  $baseBps,
        Carbon $from,
        Carbon $until,
        int    $interval   // menit
    ): void {
        $nowStr = now()->toDateTimeString();
        $cur    = $from->copy();
        // Cache multiplier musiman per bulan agar konsisten dalam satu segmen
        $seasonalCache = [];

        while ($cur->lessThan($until)) {
            $multiplier = $this->getMultiplier($cur, $seasonalCache);
            // In dan Out punya noise independen → variatif satu sama lain
            $inBps  = max(100_000, (int) ($baseBps * $multiplier * $this->noise()));
            $outBps = max(100_000, (int) ($baseBps * $multiplier * 0.4 * $this->noise())); // out ~40% dari in

            $rows[] = [
                'opd_id'     => $opdId,
                'timestamp'  => $cur->toDateTimeString(),
                'in_bps'     => $inBps,
                'out_bps'    => $outBps,
                'created_at' => $nowStr,
                'updated_at' => $nowStr,
            ];

            $cur->addMinutes($interval);
        }
    }

    // -------------------------------------------------------------------------
    // Hitung multiplier berdasarkan jam, hari, dan bulan
    // -------------------------------------------------------------------------

    private function getMultiplier(Carbon $dt, array &$seasonalCache): float
    {
        // 1. FAKTOR JAM — jam sibuk 08-17 tinggi, subuh rendah
        $hour        = (int) $dt->format('H');
        $hourFactor  = match (true) {
            $hour >= 8  && $hour < 12  => mt_rand(120, 180) / 100,   // pagi  : 1.2–1.8×
            $hour >= 12 && $hour < 14  => mt_rand(80,  120) / 100,   // siang : turun saat istirahat
            $hour >= 14 && $hour < 17  => mt_rand(110, 160) / 100,   // sore  : 1.1–1.6×
            $hour >= 17 && $hour < 20  => mt_rand(60,  100) / 100,   // petang: mulai turun
            $hour >= 20 || $hour < 6   => mt_rand(10,   40) / 100,   // malam : 0.1–0.4×
            default                    => mt_rand(40,   80) / 100,   // transisi
        };

        // 2. FAKTOR HARI — akhir pekan sepi
        $dayOfWeek  = (int) $dt->format('N'); // 1=Mon, 7=Sun
        $dayFactor  = match (true) {
            $dayOfWeek >= 6             => mt_rand(20, 50) / 100,    // Sabtu-Minggu: 0.2–0.5×
            $dayOfWeek === 1            => mt_rand(70, 90) / 100,    // Senin: masih warmup
            $dayOfWeek === 5            => mt_rand(80, 110) / 100,   // Jumat: agak santai
            default                     => mt_rand(90, 130) / 100,   // Sel-Kam: normal
        };

        // 3. FAKTOR MUSIMAN per bulan — cached agar satu bulan konsisten
        $monthKey = $dt->format('Y-m');
        if (!isset($seasonalCache[$monthKey])) {
            // Setiap bulan punya "karakter" tersendiri: 0.5× – 2.0×
            $seasonalCache[$monthKey] = mt_rand(50, 200) / 100;
        }
        $seasonalFactor = $seasonalCache[$monthKey];

        return $hourFactor * $dayFactor * $seasonalFactor;
    }

    // -------------------------------------------------------------------------
    // Noise ±30% acak agar garis tidak terlalu mulus
    // -------------------------------------------------------------------------

    private function noise(): float
    {
        return mt_rand(70, 130) / 100;
    }
}
