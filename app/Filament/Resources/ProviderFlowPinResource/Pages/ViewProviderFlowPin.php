<?php

namespace App\Filament\Resources\ProviderFlowPinResource\Pages;

use App\Filament\Resources\ProviderFlowPinResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProviderFlowPin extends ViewRecord
{
    protected static string $resource = ProviderFlowPinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
