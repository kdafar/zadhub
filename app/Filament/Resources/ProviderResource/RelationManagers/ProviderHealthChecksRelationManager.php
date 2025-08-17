<?php

namespace App\Filament\Resources\ProviderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProviderHealthChecksRelationManager extends RelationManager
{
    protected static string $relationship = 'healthChecks'; // Provider::healthChecks()

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('status')
                ->options([
                    'ok' => 'OK',
                    'degraded' => 'Degraded',
                    'down' => 'Down',
                ])
                ->required(),
            Forms\Components\DateTimePicker::make('checked_at')->required(),
            Forms\Components\Textarea::make('details_json')->rows(6)->label('Details (JSON)'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'ok',
                        'warning' => 'degraded',
                        'danger' => 'down',
                    ]),
                Tables\Columns\TextColumn::make('checked_at')->since(),
                Tables\Columns\TextColumn::make('details_json')->limit(80),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Add check'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
