<?php

namespace App\Filament\Resources\LogActivityResource\Pages;

use App\Filament\Resources\LogActivityResource;
use App\Filament\Resources\LogActivityResource\Widgets\DailyBandwidthChart;
use App\Filament\Resources\LogActivityResource\Widgets\MonthlyBandwidthChart;
use App\Filament\Resources\LogActivityResource\Widgets\WeeklyBandwidthChart;
use App\Filament\Resources\LogActivityResource\Widgets\YearlyBandwidthChart;
use App\Models\Opd;
// use App\Filament\Resources\LogActivityResource\Widgets\BandwidthTrafficChart;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;

class ListLogActivities extends ListRecords
{
    protected static string $resource = LogActivityResource::class;

    public ?int $opdId = null;

    public string $opdName = 'Semua OPD';

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Actions\CreateAction::make(),
    //     ];
    // }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filterOpd')
                // Label tombol berubah sesuai OPD yang aktif
                ->label('OPD: ' . $this->opdName)
                ->icon('heroicon-o-building-office-2')
                // Warna primary jika ada filter aktif, abu-abu jika "Semua OPD"
                ->color($this->opdId ? 'primary' : 'gray')
                ->form([
                    Select::make('opd_id')
                        ->label('Pilih OPD')
                        ->options(
                            Opd::orderBy('nama_opd')->pluck('nama_opd', 'id')
                        )
                        ->placeholder('Semua OPD')
                        ->default($this->opdId)
                        ->nullable()
                        ->searchable(),
                ])
                ->action(function (array $data): void {
                    // Update state halaman
                    $this->opdId   = $data['opd_id'] ? (int) $data['opd_id'] : null;
                    $this->opdName = $this->opdId
                        ? (Opd::find($this->opdId)?->nama_opd ?? 'Semua OPD')
                        : 'Semua OPD';

                    // Dispatch Livewire event → semua widget chart mendengarkan ini
                    // lewat #[On('opdFilterUpdated')] di BaseBandwidthChart
                    $this->dispatch('opdFilterUpdated', opdId: $this->opdId);
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DailyBandwidthChart::class,
            WeeklyBandwidthChart::class,
            MonthlyBandwidthChart::class,
            YearlyBandwidthChart::class,
            // BandwidthTrafficChart::class,
        ];
    }


    protected function getHeaderWidgetsColumns(): int|array
    {
        return [
            'default' => 1,   // mobile: 1 kolom
            'lg'      => 2,   // desktop: 2 kolom (grid 2×2)
        ];
    }
}
