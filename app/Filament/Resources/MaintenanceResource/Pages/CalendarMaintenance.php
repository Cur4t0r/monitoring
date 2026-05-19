<?php

namespace App\Filament\Resources\MaintenanceResource\Pages;

use App\Filament\Resources\MaintenanceResource;
// use App\Filament\Widgets\MaintenanceCalendarWidget;
use Filament\Actions;
use Filament\Resources\Pages\Page;

class CalendarMaintenance extends Page
{
    protected static string $resource = MaintenanceResource::class;

    protected static string $view = 'filament.maintenance.calendar';

    protected static ?string $title = 'Kalender Maintenance';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('list')
                ->label('Lihat Daftar')
                ->icon('heroicon-o-list-bullet')
                ->color('gray')
                ->url(MaintenanceResource::getUrl('index')),
        ];
    }

    // protected function getHeaderWidgets(): array
    // {
    //     return [
    //         MaintenanceCalendarWidget::class,
    //     ];
    // }

    // public function getHeaderWidgetsColumns(): int | array
    // {
    //     return 1;
    // }
}
