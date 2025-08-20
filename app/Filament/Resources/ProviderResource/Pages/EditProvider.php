<?php

namespace App\Filament\Resources\ProviderResource\Pages;

use App\Filament\Resources\ProviderResource;
use App\Services\ProviderOnboardingService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditProvider extends EditRecord
{
    protected static string $resource = ProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('testCredentials')
                ->label('Test credentials')
                ->action(function () {
                    // TODO: wire to a real service ping/job
                    Notification::make()
                        ->title('Credentials test queued')
                        ->success()
                        ->send();
                })
                ->color('success')
                ->icon('heroicon-o-check-badge'),

            Actions\Action::make('runHealthCheck')
                ->label('Run health check')
                ->action(function () {
                    // TODO: dispatch health check job
                    Notification::make()
                        ->title('Health check started')
                        ->success()
                        ->send();
                })
                ->icon('heroicon-o-heart'),

            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->wasChanged('service_type_id')) {
            $onboardingService = app(ProviderOnboardingService::class);
            $onboardingService->onboard($this->record);
        }
    }
}
