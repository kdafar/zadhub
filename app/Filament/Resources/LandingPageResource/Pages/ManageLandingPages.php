<?php

namespace App\Filament\Resources\LandingPageResource\Pages;

use App\Filament\Resources\LandingPageResource;
use App\Services\RevalidateService;
use Filament\Resources\Pages\ManageRecords;

class ManageLandingPages extends ManageRecords
{
    protected static string $resource = LandingPageResource::class;

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        if ($record && $record->is_published) {
            app(RevalidateService::class)->trigger([
                "/{$record->locale}/{$record->slug}",
            ]);
        }
    }
}
