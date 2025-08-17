<?php

namespace App\Filament\Resources\ProviderRoutingRuleResource\Pages;

use App\Filament\Resources\ProviderRoutingRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProviderRoutingRules extends ListRecords
{
    protected static string $resource = ProviderRoutingRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
