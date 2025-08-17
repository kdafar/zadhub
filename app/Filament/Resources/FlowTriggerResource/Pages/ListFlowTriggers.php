<?php

namespace App\Filament\Resources\FlowTriggerResource\Pages;

use App\Filament\Resources\FlowTriggerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFlowTriggers extends ListRecords
{
    protected static string $resource = FlowTriggerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
