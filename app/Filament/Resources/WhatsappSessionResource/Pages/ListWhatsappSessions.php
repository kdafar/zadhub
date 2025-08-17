<?php

namespace App\Filament\Resources\WhatsappSessionResource\Pages;

use App\Filament\Resources\WhatsappSessionResource;
use Filament\Resources\Pages\ListRecords;

class ListWhatsappSessions extends ListRecords
{
    protected static string $resource = WhatsappSessionResource::class;

    protected ?string $heading = 'WhatsApp Sessions';
}
