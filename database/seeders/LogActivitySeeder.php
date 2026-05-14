<?php

namespace Database\Seeders;

use App\Models\LogActivity;
use App\Models\Opd;
use App\Services\LogActivityService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Run the database seeds.
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
    private array $opdBaseMap  = [];
    private array $seasonCache = [];

    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('log_activities')->truncate();
        Schema::enableForeignKeyConstraints();

        $now  = now();
        $opds = Opd::orderBy('id')->get(['id']);

        // Base bandwidth berbeda per OPD agar grafik tiap OPD punya karakter sendiri
        foreach ($opds as $i => $opd) {
            $this->opdBaseMap[$opd->id] = LogActivityService::BPS_MIN
                + ($i * ((LogActivityService::BPS_MAX - LogActivityService::BPS_MIN)
                    / max(1, $opds->count() - 1)));
        }

        $this->command->getOutput()->progressStart($opds->count());

        foreach ($opds as $opd) {
            $rows = [];
            $base = $this->opdBaseMap[$opd->id];
            $this->seasonCache = []; // reset cache musiman per OPD

            // Segmen 1: 24 jam terakhir → 5 menit (chart Harian)
            $this->fillSegment(
                $rows,
                $opd->id,
                $base,
                from: $now->copy()->subDay(),
                until: $now,
                interval: 5
            );

            // Segmen 2: 1–7 hari lalu → 30 menit (chart Mingguan)
            $this->fillSegment(
                $rows,
                $opd->id,
                $base,
                from: $now->copy()->subDays(7),
                until: $now->copy()->subDay(),
                interval: 30
            );

            // Segmen 3: 7–30 hari lalu → 1 jam (chart Bulanan)
            $this->fillSegment(
                $rows,
                $opd->id,
                $base,
                from: $now->copy()->subDays(30),
                until: $now->copy()->subDays(7),
                interval: 60
            );

            // Segmen 4: 30 hari–1 tahun lalu → 6 jam (chart Tahunan)
            $this->fillSegment(
                $rows,
                $opd->id,
                $base,
                from: $now->copy()->subYear(),
                until: $now->copy()->subDays(30),
                interval: 360
            );

            // Bulk insert per OPD untuk performa — factory tetap dipakai
            // untuk menghasilkan data individual di fillSegment()
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('log_activities')->insert($chunk);
            }

            $this->command->getOutput()->progressAdvance();
        }

        $this->command->getOutput()->progressFinish();
        $this->command->info('Seeder selesai — semua chart langsung terisi.');
        $this->command->line('Jalankan: php artisan schedule:work');
    }

    private function fillSegment(
        array  &$rows,
        int    $opdId,
        float  $baseBps,
        Carbon $from,
        Carbon $until,
        int    $interval
    ): void {
        $nowStr = now()->toDateTimeString();
        $cur    = $from->copy();

        while ($cur->lessThan($until)) {
            $multiplier = $this->getMultiplier($cur);

            // Gunakan factory untuk bps, tapi override dengan pola realistis
            $inBps  = max(100_000, (int) ($baseBps * $multiplier * $this->noise()));
            $outBps = max(100_000, (int) ($baseBps * $multiplier * 0.4 * $this->noise()));

            $base = LogActivity::factory()->make([
                'opd_id'    => $opdId,
                'timestamp' => $cur->toDateTimeString(),
                'in_bps'    => $inBps,
                'out_bps'   => $outBps,
            ])->getAttributes();

            $rows[] = array_merge($base, [
                'created_at' => $nowStr,
                'updated_at' => $nowStr,
            ]);

            $cur->addMinutes($interval);
        }
    }

    private function getMultiplier(Carbon $dt): float
    {
        $hour = (int) $dt->format('H');

        $hourFactor = match (true) {
            $hour >= 8  && $hour < 12  => mt_rand(120, 180) / 100,
            $hour >= 12 && $hour < 14  => mt_rand(80,  120) / 100,
            $hour >= 14 && $hour < 17  => mt_rand(110, 160) / 100,
            $hour >= 17 && $hour < 20  => mt_rand(60,  100) / 100,
            $hour >= 20 || $hour < 6   => mt_rand(10,   40) / 100,
            default                    => mt_rand(40,   80) / 100,
        };

        $dayFactor = match ((int) $dt->format('N')) {
            6, 7    => mt_rand(20,  50) / 100,
            1       => mt_rand(70,  90) / 100,
            5       => mt_rand(80, 110) / 100,
            default => mt_rand(90, 130) / 100,
        };

        $monthKey = $dt->format('Y-m');
        if (!isset($this->seasonCache[$monthKey])) {
            $this->seasonCache[$monthKey] = mt_rand(50, 200) / 100;
        }

        $seasonalFactor = $this->seasonCache[$monthKey];

        return $hourFactor * $dayFactor * $seasonalFactor;
    }

    private function noise(): float
    {
        return mt_rand(70, 130) / 100;
    }
}
