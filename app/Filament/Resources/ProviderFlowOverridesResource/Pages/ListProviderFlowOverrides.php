<?php

namespace App\Filament\Resources\ProviderFlowOverridesResource\Pages;

use App\Filament\Resources\ProviderFlowOverridesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProviderFlowOverrides extends ListRecords
{
    protected static string $resource = ProviderFlowOverridesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
