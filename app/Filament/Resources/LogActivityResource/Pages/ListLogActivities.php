<?php

namespace App\Filament\Resources\LogActivityResource\Pages;

use App\Filament\Resources\LogActivityResource;
use App\Filament\Resources\LogActivityResource\Widgets\DailyBandwidthChart;
use App\Filament\Resources\LogActivityResource\Widgets\MonthlyBandwidthChart;
use App\Filament\Resources\LogActivityResource\Widgets\WeeklyBandwidthChart;
use App\Filament\Resources\LogActivityResource\Widgets\YearlyBandwidthChart;
use App\Models\Opd;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;

class ListLogActivities extends ListRecords
{
    protected static string $resource = LogActivityResource::class;

    public ?int $opdId = null;

    public string $opdName = 'Semua OPD';

    // -------------------------------------------------------------------------
    // Header action — filter OPD
    // -------------------------------------------------------------------------

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filterOpd')
                // FIX: gunakan closure agar label dievaluasi ulang setiap render,
                // bukan hanya saat mount. Tanpa closure, nilai string di-capture
                // sekali pada saat getHeaderActions() dipanggil pertama kali.
                ->label(fn(): string => 'OPD: ' . $this->opdName)
                ->icon('heroicon-o-building-office-2')
                ->color(fn(): string => $this->opdId ? 'primary' : 'gray')
                ->form([
                    Select::make('opd_id')
                        ->label('Pilih OPD')
                        ->options(Opd::orderBy('nama_opd')->pluck('nama_opd', 'id'))
                        ->placeholder('Semua OPD')
                        ->default($this->opdId)
                        ->nullable()
                        ->searchable(),
                ])
                ->action(function (array $data): void {
                    $this->opdId   = $data['opd_id'] ? (int) $data['opd_id'] : null;
                    $this->opdName = $this->opdId
                        ? (Opd::find($this->opdId)?->nama_opd ?? 'Semua OPD')
                        : 'Semua OPD';

                    // Broadcast ke semua widget chart
                    $this->dispatch('opdFilterUpdated', opdId: $this->opdId);
                }),
        ];
    }

    // -------------------------------------------------------------------------
    // Header widgets — grid 2×2
    // -------------------------------------------------------------------------

    protected function getHeaderWidgets(): array
    {
        return [
            DailyBandwidthChart::class,
            WeeklyBandwidthChart::class,
            MonthlyBandwidthChart::class,
            YearlyBandwidthChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return [
            'default' => 1,
            'lg'      => 2,
        ];
    }
}
