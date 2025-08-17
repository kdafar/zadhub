<?php

namespace App\Filament\Resources\ProviderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProviderRateLimitsRelationManager extends RelationManager
{
    protected static string $relationship = 'rateLimits'; // Provider::rateLimits()

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('key')->required()->maxLength(255),
            Forms\Components\TextInput::make('window_seconds')->numeric()->minValue(1)->required(),
            Forms\Components\TextInput::make('max_requests')->numeric()->minValue(1)->required(),
            Forms\Components\TextInput::make('current_count')->numeric()->minValue(0)->default(0),
            Forms\Components\DateTimePicker::make('reset_at'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')->searchable(),
                Tables\Columns\TextColumn::make('window_seconds')->label('Window (s)'),
                Tables\Columns\TextColumn::make('max_requests')->label('Max req'),
                Tables\Columns\TextColumn::make('current_count')->label('Used'),
                Tables\Columns\TextColumn::make('reset_at')->since()->label('Resets'),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
