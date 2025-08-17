<?php

namespace App\Filament\Resources\WhatsappSessionResource\Pages;

use App\Filament\Resources\WhatsappSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWhatsappSession extends EditRecord
{
    protected static string $resource = WhatsappSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
