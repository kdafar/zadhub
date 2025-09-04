<?php

namespace App\Filament\Resources\LandingLeadResource\Pages;

use App\Filament\Resources\LandingLeadResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLandingLeads extends ListRecords
{
    protected static string $resource = LandingLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
