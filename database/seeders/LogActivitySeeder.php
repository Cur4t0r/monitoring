<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Opd;
use Carbon\Carbon;

/**
 * Seeder ini hanya mengisi DATA HISTORIS (masa lalu) agar chart
 * Mingguan, Bulanan, dan Tahunan punya data sejak pertama deploy.
 *
 * Data "Harian" (24 jam terakhir) akan SEGERA terisi otomatis
 * begitu scheduler log:generate berjalan (setiap 5 menit).
 *
 * Karena seeder ini tidak menyentuh range 0-24 jam terakhir,
 * tidak akan ada duplikasi dengan data yang dibuat scheduler.
 */
class LogActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        DB::table('log_activities')->truncate();

        $now  = now();
        $opds = Opd::pluck('id');

        $this->command->getOutput()->progressStart($opds->count());

        foreach ($opds as $opdId) {
            $rows = [];

            // ------------------------------------------------------------------
            // Segmen 1: 24 jam - 7 hari lalu (setiap 30 menit)
            // → Mengisi chart MINGGUAN
            // ------------------------------------------------------------------
            $this->fillSegment(
                rows: $rows,
                opdId: $opdId,
                from: $now->copy()->subDays(7),
                until: $now->copy()->subDay(),      // berhenti 24 jam lalu
                interval: 30,                          // menit
            );

            // ------------------------------------------------------------------
            // Segmen 2: 7 hari - 30 hari lalu (setiap 1 jam)
            // → Mengisi chart BULANAN
            // ------------------------------------------------------------------
            $this->fillSegment(
                rows: $rows,
                opdId: $opdId,
                from: $now->copy()->subDays(30),
                until: $now->copy()->subDays(7),
                interval: 60,
            );

            // ------------------------------------------------------------------
            // Segmen 3: 30 hari - 1 tahun lalu (setiap 6 jam)
            // → Mengisi chart TAHUNAN
            // ------------------------------------------------------------------
            $this->fillSegment(
                rows: $rows,
                opdId: $opdId,
                from: $now->copy()->subYear(),
                until: $now->copy()->subDays(30),
                interval: 360,
            );

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('log_activities')->insert($chunk);
            }

            $this->command->getOutput()->progressAdvance();
        }

        $this->command->getOutput()->progressFinish();
        $this->command->info('Seeder selesai. Jalankan scheduler agar data harian terisi otomatis:');
        $this->command->line('  php artisan schedule:work     (development)');
        $this->command->line('  atau tambahkan cron entry     (production)');
    }

    private function fillSegment(
        array    &$rows,
        int      $opdId,
        Carbon   $from,
        Carbon   $until,
        int      $interval,    // dalam menit
    ): void {
        $now = now()->toDateTimeString();
        $cur = $from->copy();

        while ($cur->lessThan($until)) {
            $rows[] = [
                'opd_id'     => $opdId,
                'timestamp'  => $cur->toDateTimeString(),
                'in_bps'     => rand(300_000, 40_000_000),
                'out_bps'    => rand(300_000, 40_000_000),
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $cur->addMinutes($interval);
        }
    }
}
