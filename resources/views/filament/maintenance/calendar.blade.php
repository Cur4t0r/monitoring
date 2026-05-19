{{-- <x-filament-panels::page>
    <x-filament-panels::widgets :widgets="$this->getHeaderWidgets()" :columns="$this->getHeaderWidgetsColumns()" />
</x-filament-panels::page> --}}

{{-- <x-filament-panels::page>
    @php
        $widgets = $this->getHeaderWidgets();
    @endphp

    <div class="grid gap-6">
        @foreach ($widgets as $widget)
            @livewire($widget, key($widget))
        @endforeach
    </div>
</x-filament-panels::page> --}}

<x-filament-panels::page>
    @livewire(\App\Filament\Widgets\MaintenanceCalendarWidget::class)
</x-filament-panels::page>
