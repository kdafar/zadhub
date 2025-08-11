<?php

namespace App\Filament\Resources\FlowResource\Pages;

use App\Filament\Resources\FlowResource;
use App\Models\FlowTemplate;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFlow extends EditRecord
{
    protected static string $resource = FlowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Find the template the admin selected
        $template = FlowTemplate::find($data['flow_template_id']);

        // Set the live_version_id from the template
        if ($template) {
            $data['live_version_id'] = $template->live_version_id;
        }

        return $data;
    }
}
