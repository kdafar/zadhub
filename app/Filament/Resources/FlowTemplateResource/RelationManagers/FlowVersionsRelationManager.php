<?php

namespace App\Filament\Resources\FlowTemplateResource\RelationManagers;

use App\Models\FlowVersion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class FlowVersionsRelationManager extends RelationManager
{
    // ✅ MUST be the relation name on FlowTemplate
    protected static string $relationship = 'versions';

    // (optional) label/title
    protected static ?string $title = 'Versions';

    protected static ?string $recordTitleAttribute = 'version';

    // ❌ DO NOT add: protected static ?string $model = FlowVersion::class;
    // Filament infers the model from the relationship.

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('version')
                ->numeric()
                ->required(),

            Forms\Components\Toggle::make('is_stable')
                ->label('Stable'),

            Forms\Components\KeyValue::make('schema_json')
                ->label('Schema JSON')
                ->reorderable()
                ->addActionLabel('Add')
                ->keyLabel('Key')
                ->valueLabel('Value'),

            Forms\Components\KeyValue::make('components_json')
                ->label('Components JSON')
                ->reorderable()
                ->addActionLabel('Add'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('version')
                    ->label('Version')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_stable')
                    ->boolean()
                    ->label('Stable'),
                Tables\Columns\TextColumn::make('created_at')->since(),
                Tables\Columns\TextColumn::make('updated_at')->since(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
