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
            Actions\Action::make('import')
                ->label('Import from JSON')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    \Filament\Forms\Components\TextInput::make('name')
                        ->label('New Template Name')
                        ->required(),
                    \Filament\Forms\Components\Select::make('service_type_id')
                        ->label('Service Type')
                        ->options(\App\Models\ServiceType::query()->orderBy('name')->pluck('name', 'id'))
                        ->required(),
                    \Filament\Forms\Components\FileUpload::make('json_file')
                        ->label('JSON File')
                        ->acceptedFileTypes(['application/json'])
                        ->required(),
                ])
                ->action(function (array $data) {
                    try {
                        $jsonContent = file_get_contents($data['json_file']->getRealPath());
                        $definition = json_decode($jsonContent, true);

                        if (json_last_error() !== JSON_ERROR_NONE || ! isset($definition['screens'])) {
                            \Filament\Notifications\Notification::make()
                                ->title('Import Failed')
                                ->body('Invalid JSON file or missing "screens" key.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $template = \App\Models\FlowTemplate::create([
                            'name' => $data['name'],
                            'slug' => \Illuminate\Support\Str::slug($data['name']),
                            'service_type_id' => $data['service_type_id'],
                        ]);

                        $version = $template->versions()->create([
                            'version' => 1,
                            'definition' => $definition,
                            'is_template' => true,
                            'service_type_id' => $data['service_type_id'],
                            'status' => 'draft',
                        ]);

                        $template->update(['latest_version_id' => $version->id]);

                        \Filament\Notifications\Notification::make()
                            ->title('Import Successful')
                            ->body("Successfully imported '{$data['name']}' template.")
                            ->success()
                            ->send();

                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Import Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
