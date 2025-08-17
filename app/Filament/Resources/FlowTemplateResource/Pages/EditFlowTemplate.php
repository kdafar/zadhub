<?php

namespace App\Filament\Resources\FlowTemplateResource\Pages;

use App\Filament\Resources\FlowTemplateResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditFlowTemplate extends EditRecord
{
    protected static string $resource = FlowTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('newVersion')
                ->label('Create new version from latest')
                ->icon('heroicon-o-plus')
                ->requiresConfirmation()
                ->action(function () {
                    $tpl = $this->getRecord();

                    $latest = $tpl->versions()->orderByDesc('version')->first();
                    $next = ($latest?->version ?? 0) + 1;

                    $tpl->versions()->create([
                        'version' => $next,
                        'is_stable' => false,
                        'schema_json' => $latest?->schema_json ?? ['v' => '1.0', 'entry' => 'HOME'],
                        'components_json' => $latest?->components_json ?? ['screens' => []],
                    ]);

                    Notification::make()->title("Version v{$next} created")->success()->send();
                }),

            Actions\DeleteAction::make(),
        ];
    }
}
