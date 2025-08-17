<?php

namespace App\Filament\Resources\ProviderFlowPinResource\Pages;

use App\Filament\Resources\ProviderFlowPinResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProviderFlowPins extends ListRecords
{
    protected static string $resource = ProviderFlowPinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
