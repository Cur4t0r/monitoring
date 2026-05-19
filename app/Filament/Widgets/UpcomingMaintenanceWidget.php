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
    protected int | string | array $columnSpan = [
        'default' => 1,
        'md'      => 1,
        'xl'      => 1,
    ];

    protected static ?int $sort = 4;

    /**
     * Get the planned maintenances.
     */
    public function getPlannedMaintenances(): Collection
    {
        return Maintenance::with('opds:id,nama_opd')
            ->where('status', 'planned')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at', 'asc')
            ->limit(5)
            ->get();
    }

    /**
     * Get the in-progress maintenances.
     */
    public function getInProgressMaintenances(): Collection
    {
        return Maintenance::with('opds:id,nama_opd')
            ->where('status', 'in_progress')
            ->orderBy('scheduled_at', 'asc')
            ->limit(3)
            ->get();
    }

    /**
     * Get the URL for viewing all maintenances.
     */
    public function getViewAllUrl(): string
    {
        return MaintenanceResource::getUrl('index');
    }
}
