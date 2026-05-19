<?php

namespace App\Filament\Widgets;

use App\Services\LogActivityService;
use App\Models\Opd;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OpdStatusOverview extends BaseWidget
{
    // Auto-refresh setiap 30 detik agar dashboard tetap update
    protected static ?string $pollingInterval = '30s';

    protected static ?int $sort = 1;

    // Full width — selalu memenuhi seluruh lebar grid dashboard
    protected int | string | array $columnSpan = 'full';

    // Hitung jumlah OPD total, online, dan offline untuk ditampilkan di widget
    protected function getStats(): array
    {
        // Gunakan service untuk hitung jumlah OPD online/offline
        // Online = punya record dalam 10 menit terakhir
        $service   = app(LogActivityService::class);
        $totalOpd = Opd::count();
        $onlineCount = $service->getOnlineCount();
        $offlineCount = $totalOpd - $onlineCount;

        // Persentase uptime
        $uptimePercent = $totalOpd > 0
            ? round(($onlineCount / $totalOpd) * 100, 1)
            : 0;

        return [
            // Total OPD terdaftar
            Stat::make('Total OPD', $totalOpd)
                ->description('Perangkat terdaftar')
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('gray'),

            // OPD Online (Uptime)
            Stat::make('Online', $onlineCount)
                ->description($uptimePercent . '% dari total OPD')
                ->descriptionIcon('heroicon-o-signal')
                ->color($onlineCount === $totalOpd ? 'success' : 'warning')
                ->chart($service->getOnlineSparkline()),

            // OPD Offline (Downtime)
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
}
