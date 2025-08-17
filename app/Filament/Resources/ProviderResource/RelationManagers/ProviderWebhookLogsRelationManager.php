<?php

namespace App\Filament\Resources\ProviderResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProviderWebhookLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'webhookLogs'; // Provider::webhookLogs()

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('url')
            ->columns([
                Tables\Columns\BadgeColumn::make('direction')
                    ->colors([
                        'primary' => 'incoming',
                        'success' => 'outgoing',
                    ]),
                Tables\Columns\TextColumn::make('status_code'),
                Tables\Columns\TextColumn::make('url')->limit(50)->tooltip(fn ($record) => $record->url),
                Tables\Columns\TextColumn::make('took_ms')->label('ms'),
                Tables\Columns\TextColumn::make('created_at')->since()->label('At'),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make()->modalHeading('Webhook log')->form([
                    \Filament\Forms\Components\Textarea::make('headers_json')->rows(6)->disabled()->label('Headers'),
                    \Filament\Forms\Components\Textarea::make('payload_json')->rows(10)->disabled()->label('Payload'),
                    \Filament\Forms\Components\Textarea::make('error')->rows(4)->disabled()->label('Error'),
                ]),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([]);
    }
}
