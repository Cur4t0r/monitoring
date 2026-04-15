<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Opd;
use Carbon\Carbon;

class LogActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Hapus data lama agar tidak duplikat saat re-seed
        DB::table('log_activities')->truncate();

        $now  = now();
        $opds = Opd::pluck('id');

        foreach ($opds as $opdId) {
            $rows = [];

            // ------------------------------------------------------------------
            // Segmen 1 — 24 jam terakhir, setiap 5 menit (288 titik)
            // ------------------------------------------------------------------
            $start = $now->copy()->subDay();
            for ($i = 0; $i < 288; $i++) {
                $rows[] = $this->makeRow($opdId, $start->copy()->addMinutes($i * 5));
            }

            // ------------------------------------------------------------------
            // Segmen 2 — 1–7 hari lalu, setiap 30 menit (336 titik)
            // ------------------------------------------------------------------
            $start = $now->copy()->subDays(7);
            $end   = $now->copy()->subDay();
            $cur   = $start->copy();
            while ($cur->lessThan($end)) {
                $rows[] = $this->makeRow($opdId, $cur->copy());
                $cur->addMinutes(30);
            }

            // ------------------------------------------------------------------
            // Segmen 3 — 7–30 hari lalu, setiap 1 jam (552 titik)
            // ------------------------------------------------------------------
            $start = $now->copy()->subDays(30);
            $end   = $now->copy()->subDays(7);
            $cur   = $start->copy();
            while ($cur->lessThan($end)) {
                $rows[] = $this->makeRow($opdId, $cur->copy());
                $cur->addHour();
            }

            // ------------------------------------------------------------------
            // Segmen 4 — 30 hari–1 tahun lalu, setiap 6 jam (~1340 titik)
            // ------------------------------------------------------------------
            $start = $now->copy()->subYear();
            $end   = $now->copy()->subDays(30);
            $cur   = $start->copy();
            while ($cur->lessThan($end)) {
                $rows[] = $this->makeRow($opdId, $cur->copy());
                $cur->addHours(6);
            }

            // ------------------------------------------------------------------
            // Bulk insert per OPD (lebih cepat dari Eloquent create() per baris)
            // ------------------------------------------------------------------
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('log_activities')->insert($chunk);
            }

            $this->command->info("Seeded OPD ID {$opdId}: " . count($rows) . ' records');
        }
    }

    private function makeRow(int $opdId, Carbon $timestamp): array
    {
        return [
            'opd_id'     => $opdId,
            'timestamp'  => $timestamp->toDateTimeString(),
            'in_bps'     => rand(300_000, 40_000_000),   // 0.3 – 40 Mbps
            'out_bps'    => rand(300_000, 40_000_000),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];
    }
}
