<?php

namespace App\Filament\Resources\FlowVersionResource\Pages;

use App\Filament\Resources\FlowVersionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFlowVersions extends ListRecords
{
    protected static string $resource = FlowVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
