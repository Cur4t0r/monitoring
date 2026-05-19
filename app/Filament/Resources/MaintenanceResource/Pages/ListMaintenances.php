<?php

namespace App\Filament\Resources\MaintenanceResource\Pages;

use App\Filament\Resources\MaintenanceResource;
use App\Filament\Widgets\MaintenanceCalendarWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaintenances extends ListRecords
{
    protected static string $resource = MaintenanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Maintenance'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MaintenanceCalendarWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }
}
