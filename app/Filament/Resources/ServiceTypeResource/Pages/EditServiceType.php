<?php

namespace App\Filament\Resources\ServiceTypeResource\Pages;

use App\Filament\Resources\ServiceTypeResource;
use App\Models\FlowTemplate;
use App\Models\Provider;
use App\Services\Meta\MetaFetchService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditServiceType extends EditRecord
{
    protected static string $resource = ServiceTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('importFromMeta')
                ->label('Import from Meta')
                ->icon('heroicon-o-arrow-down-tray')
                ->form([
                    Forms\Components\Select::make('provider_id')
                        ->label('Provider to fetch from')
                        ->options($this->record->providers()->pluck('name', 'id'))
                        ->live()
                        ->required(),
                    Forms\Components\CheckboxList::make('message_templates_to_import')
                        ->label('Message Templates')
                        ->options(function (Forms\Get $get) {
                            $providerId = $get('provider_id');
                            if (! $providerId) {
                                return [];
                            }
                            try {
                                $templates = app(MetaFetchService::class)->fetchMessageTemplates(Provider::find($providerId));

                                return collect($templates)
                                    ->where('status', 'APPROVED')
                                    ->pluck('name', 'name')
                                    ->toArray();
                            } catch (\Exception $e) {
                                Notification::make()->title('Failed to fetch message templates')->body($e->getMessage())->danger()->send();

                                return [];
                            }
                        })
                        ->helperText('Only approved templates will be shown.')
                        ->columns(2),
                    Forms\Components\CheckboxList::make('flows_to_import')
                        ->label('Flows')
                        ->options(function (Forms\Get $get) {
                            $providerId = $get('provider_id');
                            if (! $providerId) {
                                return [];
                            }
                            try {
                                $flows = app(MetaFetchService::class)->fetchFlows(Provider::find($providerId));

                                return collect($flows)->pluck('name', 'id')->toArray();
                            } catch (\Exception $e) {
                                Notification::make()->title('Failed to fetch flows')->body($e->getMessage())->danger()->send();

                                return [];
                            }
                        })
                        ->columns(2),
                ])
                ->action(function (array $data) {
                    $provider = Provider::find($data['provider_id']);
                    $fetcher = app(MetaFetchService::class);
                    $serviceType = $this->record;

                    // Import Message Templates
                    if (! empty($data['message_templates_to_import'])) {
                        $allTemplates = collect($fetcher->fetchMessageTemplates($provider));
                        $existingTemplates = $serviceType->message_templates ?? [];
                        foreach ($data['message_templates_to_import'] as $templateName) {
                            $templateData = $allTemplates->firstWhere('name', $templateName);
                            if ($templateData) {
                                $existingTemplates[$templateName] = $templateData; // Store full object
                            }
                        }
                        $serviceType->update(['message_templates' => $existingTemplates]);
                    }

                    // Import Flows
                    if (! empty($data['flows_to_import'])) {
                        foreach ($data['flows_to_import'] as $flowId) {
                            $flowData = $fetcher->fetchFlowDefinition($flowId, $provider);
                            $flowInfo = collect($fetcher->fetchFlows($provider))->firstWhere('id', $flowId);

                            $template = FlowTemplate::create([
                                'name' => '[META] '.($flowInfo['name'] ?? 'Imported Flow '.$flowId),
                                'slug' => Str::slug('meta '.($flowInfo['name'] ?? 'imported-'.$flowId)),
                                'service_type_id' => $serviceType->id,
                            ]);

                            $version = $template->versions()->create([
                                'version' => 1,
                                'definition' => $flowData,
                                'is_template' => true,
                                'service_type_id' => $serviceType->id,
                                'status' => 'published', // Imported templates are already approved/published
                                'published_at' => now(),
                                'meta' => ['meta_flow_id' => $flowId],
                            ]);

                            $template->update(['latest_version_id' => $version->id]);
                        }
                    }

                    Notification::make()
                        ->title('Import Successful')
                        ->body('Selected assets have been imported and associated with this Service Type.')
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
