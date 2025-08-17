<?php

namespace App\Filament\Resources\FlowTriggerResource\Pages;

use App\Filament\Resources\FlowTriggerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFlowTrigger extends EditRecord
{
    protected static string $resource = FlowTriggerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
