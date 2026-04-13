<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Opd;
use App\Models\LogActivity;

class LogActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        foreach (Opd::all() as $opd) {

            /**
             * DAILY GRAPH (5 Minute Average)
             * 24 jam x 60 / 5 = 288 data
             */
            $start = now()->subDay();

            for ($i = 0; $i < 288; $i++) {
                LogActivity::create([
                    'opd_id' => $opd->id,
                    'timestamp' => $start->copy()->addMinutes($i * 5),
                    'in_bps' => rand(300_000, 40_000_000),
                    'out_bps' => rand(300_000, 40_000_000),
                ]);
            }
        }
    }
}
