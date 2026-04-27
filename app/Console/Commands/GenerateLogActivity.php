<?php

namespace App\Console\Commands;

// use App\Models\LogActivity;
use App\Models\Opd;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateLogActivity extends Command
{
    /**
     * Nama & signature command.
     * Bisa dijalankan manual: php artisan log:generate
     * Atau otomatis via scheduler setiap 5 menit.
     */
    protected $signature = 'log:generate
                            {--opd= : ID OPD tertentu (opsional, default: semua OPD)}
                            {--dry-run : Tampilkan tanpa menyimpan}';

    protected $description = 'Generate log aktivitas bandwidth untuk semua OPD (simulasi polling SNMP 5 menit)';

    public function handle(): int
    {
        $timestamp = now();

        // Filter OPD tertentu jika ada opsi --opd
        $query = Opd::query();
        if ($opdId = $this->option('opd')) {
            $query->where('id', $opdId);
        }

        $opds = $query->get(['id', 'nama_opd']);

        if ($opds->isEmpty()) {
            $this->warn('Tidak ada OPD ditemukan.');
            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('[DRY RUN] Akan generate ' . $opds->count() . ' record pada ' . $timestamp);
            return self::SUCCESS;
        }

        // Bulk insert satu record per OPD sekaligus
        $rows = $opds->map(fn($opd) => [
            'opd_id'     => $opd->id,
            'timestamp'  => $timestamp,
            'in_bps'     => rand(300_000, 40_000_000),   // 0.3 – 40 Mbps (sesuaikan range asli)
            'out_bps'    => rand(300_000, 40_000_000),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->toArray();

        DB::table('log_activities')->insert($rows);

        $this->info('[' . $timestamp->format('d/m/Y H:i:s') . '] Generated ' . count($rows) . ' records.');

        return self::SUCCESS;
    }
}
