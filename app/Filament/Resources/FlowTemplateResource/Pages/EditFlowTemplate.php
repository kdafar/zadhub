<?php

namespace App\Filament\Resources\FlowTemplateResource\Pages;

use App\Filament\Resources\FlowTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFlowTemplate extends EditRecord
{
    protected static string $resource = FlowTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
