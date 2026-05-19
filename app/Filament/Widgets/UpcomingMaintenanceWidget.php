<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\MaintenanceResource;
use App\Models\Maintenance;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class UpcomingMaintenanceWidget extends Widget
{
    protected static string $view = 'filament.widgets.upcoming-maintenance';

    // Auto-refresh setiap menit agar tetap up-to-date
    protected static ?string $pollingInterval = '60s';

    // Lebar penuh di grid dashboard
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function getUpcomingMaintenances(): Collection
    {
        return Maintenance::with('opds:id,nama_opd')
            ->where('status', 'in_progress')
            ->orderBy('scheduled_at', 'desc')
            ->limit(5)
            ->get();
    }

    public function getViewAllUrl(): string
    {
        return MaintenanceResource::getUrl('index');
    }
}
