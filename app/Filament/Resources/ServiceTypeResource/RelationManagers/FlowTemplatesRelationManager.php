<?php

namespace App\Filament\Resources\ServiceTypeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class FlowTemplatesRelationManager extends RelationManager
{
    protected static string $relationship = 'flowTemplates';

    protected static ?string $title = 'Flow Templates';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\Textarea::make('description')->rows(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slug')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('latest_version_id')
                    ->label('Latest Version')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->since()->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
