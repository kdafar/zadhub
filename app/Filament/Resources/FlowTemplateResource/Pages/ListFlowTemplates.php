<?php

namespace App\Filament\Resources\FlowTemplateResource\Pages;

use App\Filament\Resources\FlowTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFlowTemplates extends ListRecords
{
    protected static string $resource = FlowTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
