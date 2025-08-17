<?php

namespace App\Filament\Resources\ProviderFlowOverridesResource\Pages;

use App\Filament\Resources\ProviderFlowOverridesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProviderFlowOverrides extends EditRecord
{
    protected static string $resource = ProviderFlowOverridesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
