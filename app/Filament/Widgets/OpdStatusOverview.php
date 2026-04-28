<?php

namespace App\Filament\Widgets;

use App\Models\LogActivity;
use App\Models\Opd;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class OpdStatusOverview extends BaseWidget
{
    // Auto-refresh setiap 30 detik agar dashboard tetap update
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalOpd = Opd::count();

        // OPD dianggap ONLINE jika punya record dalam 10 menit terakhir
        // (toleransi 2× interval polling 5 menit)
        $threshold = now()->subMinutes(10);

        // Ambil opd_id yang punya log terbaru dalam threshold
        $onlineOpdIds = LogActivity::query()
            ->select('opd_id')
            ->where('timestamp', '>=', $threshold)
            ->distinct()
            ->pluck('opd_id');

        $onlineCount  = $onlineOpdIds->count();
        $offlineCount = $totalOpd - $onlineCount;

        // Persentase uptime
        $uptimePercent = $totalOpd > 0
            ? round(($onlineCount / $totalOpd) * 100, 1)
            : 0;

        return [
            // ------------------------------------------------------------------
            // Total OPD terdaftar
            // ------------------------------------------------------------------
            Stat::make('Total OPD', $totalOpd)
                ->description('Perangkat terdaftar')
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('gray'),

            // ------------------------------------------------------------------
            // OPD Online (Uptime)
            // ------------------------------------------------------------------
            Stat::make('Online', $onlineCount)
                ->description($uptimePercent . '% dari total OPD')
                ->descriptionIcon('heroicon-o-signal')
                ->color($onlineCount === $totalOpd ? 'success' : 'warning')
                ->chart(
                    // Sparkline 7 hari — jumlah OPD online per hari
                    $this->getOnlineChartData()
                ),

            // ------------------------------------------------------------------
            // OPD Offline (Downtime)
            // ------------------------------------------------------------------
            Stat::make('Offline', $offlineCount)
                ->description(
                    $offlineCount === 0
                        ? 'Semua perangkat online'
                        : $offlineCount . ' perangkat tidak ada data terbaru'
                )
                ->descriptionIcon(
                    $offlineCount === 0
                        ? 'heroicon-o-check-circle'
                        : 'heroicon-o-exclamation-triangle'
                )
                ->color($offlineCount === 0 ? 'success' : 'danger'),
        ];
    }

    // -------------------------------------------------------------------------
    // Sparkline — jumlah OPD yang aktif per hari selama 7 hari terakhir
    // Digunakan sebagai chart kecil di stat "Online"
    // -------------------------------------------------------------------------

    private function getOnlineChartData(): array
    {
        $days = collect();

        for ($i = 6; $i >= 0; $i--) {
            $date      = now()->subDays($i)->toDateString();
            $dateStart = $date . ' 00:00:00';
            $dateEnd   = $date . ' 23:59:59';

            // Hitung OPD yang punya minimal 1 record di hari tersebut
            $count = LogActivity::query()
                ->whereBetween('timestamp', [$dateStart, $dateEnd])
                ->distinct('opd_id')
                ->count('opd_id');

            $days->push($count);
        }

        return $days->toArray();
    }
}
