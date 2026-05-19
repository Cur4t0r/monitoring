<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Daftarkan widget kalender maintenance secara manual
        Livewire::component(
            'app.filament.widgets.maintenance-calendar-widget',
            \App\Filament\Widgets\MaintenanceCalendarWidget::class
        );
    }
}
