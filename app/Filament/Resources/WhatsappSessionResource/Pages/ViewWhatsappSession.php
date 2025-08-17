<?php

namespace App\Filament\Resources\WhatsappSessionResource\Pages;

use App\Filament\Resources\WhatsappSessionResource;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewWhatsappSession extends ViewRecord
{
    protected static string $resource = WhatsappSessionResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        /** @var \App\Models\WhatsappSession $rec */
        $rec = $this->record;

        return $infolist->schema([
            Section::make('Session')
                ->columns(3)
                ->schema([
                    TextEntry::make('phone'),
                    TextEntry::make('status'),
                    TextEntry::make('locale'),
                    TextEntry::make('provider.name')->label('Provider'),
                    TextEntry::make('service.name_en')->label('Service'),
                    TextEntry::make('current_screen')->label('Current screen'),
                    TextEntry::make('last_interacted_at')->dateTime(),
                    TextEntry::make('ended_reason')->label('Ended reason')->default('—'),
                ]),

            Section::make('Context (JSON)')->schema([
                TextEntry::make('context')
                    ->formatStateUsing(fn ($state) => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                    ->copyable()
                    ->extraAttributes(['style' => 'white-space:pre; font-family:ui-monospace,monospace;']),
            ]),

            Section::make('Last Payload (JSON)')->schema([
                TextEntry::make('last_message_type')->label('Type')->default('—'),
                TextEntry::make('last_payload')
                    ->formatStateUsing(fn ($state) => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                    ->copyable()
                    ->extraAttributes(['style' => 'white-space:pre; font-family:ui-monospace,monospace;']),
            ]),

            Section::make('Flow Timeline')->schema([
                RepeatableEntry::make('flow_history')
                    ->hiddenLabel()
                    // Sort newest first, fallback to [] if null
                    ->state(function ($record) {
                        $items = collect($record->flow_history ?? []);

                        // try to sort by 'at' desc if present
                        return $items
                            ->sortByDesc(function ($row) {
                                return $row['at'] ?? '';
                            })
                            ->values()
                            ->all();
                    })
                    ->schema([
                        TextEntry::make('at')->label('Time'),
                        TextEntry::make('event')->label('Event')->badge(),
                        TextEntry::make('screen')->label('Screen')->default('—'),
                        TextEntry::make('meta')
                            ->label('Meta')
                            ->formatStateUsing(fn ($state) => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                            ->extraAttributes(['style' => 'white-space:pre; font-family:ui-monospace,monospace;']),
                    ])
                    ->columns(3),
            ]),
        ]);
    }
}
