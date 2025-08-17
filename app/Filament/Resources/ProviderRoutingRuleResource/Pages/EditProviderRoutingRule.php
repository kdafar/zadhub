<?php

namespace App\Filament\Resources\ProviderRoutingRuleResource\Pages;

use App\Filament\Resources\ProviderRoutingRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProviderRoutingRule extends EditRecord
{
    protected static string $resource = ProviderRoutingRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
