<?php

namespace App\Filament\Resources\ProviderFlowPinResource\Pages;

use App\Filament\Resources\ProviderFlowPinResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProviderFlowPin extends EditRecord
{
    protected static string $resource = ProviderFlowPinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
