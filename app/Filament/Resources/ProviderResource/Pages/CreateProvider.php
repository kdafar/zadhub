<?php

namespace App\Filament\Resources\ProviderResource\Pages;

use App\Filament\Resources\ProviderResource;
use App\Services\ProviderOnboardingService;
use Filament\Resources\Pages\CreateRecord;

class CreateProvider extends CreateRecord
{
    protected static string $resource = ProviderResource::class;

    protected function afterCreate(): void
    {
        $onboardingService = app(ProviderOnboardingService::class);
        $onboardingService->onboard($this->record);
    }
}
