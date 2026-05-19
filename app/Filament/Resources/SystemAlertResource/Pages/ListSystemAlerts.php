<?php

namespace App\Filament\Resources\SystemAlertResource\Pages;

use App\Filament\Resources\SystemAlertResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSystemAlerts extends ListRecords
{
    protected static string $resource = SystemAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Alert'),
        ];
    }
}
