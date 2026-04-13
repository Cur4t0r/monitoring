<?php

namespace App\Filament\Resources\OpdResource\Pages;

use App\Filament\Resources\OpdResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOpd extends EditRecord
{
    protected static string $resource = OpdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
