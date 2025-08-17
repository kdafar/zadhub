<?php

namespace App\Filament\Resources\ProviderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RoutingRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'routingRules';

    protected static ?string $title = 'Routing Rules';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('rule_type')
                ->options([
                    'fixed' => 'Fixed',
                    'last_used' => 'Last Used',
                    'nearest' => 'Nearest',
                    'custom' => 'Custom',
                ])
                ->required()
                ->native(false),

            Forms\Components\KeyValue::make('rule_config')
                ->keyLabel('Key')
                ->valueLabel('Value')
                ->helperText('Free-form config JSON. E.g. for custom keyword routing: { "match": "menu|order", "flow_template_id": 1, "flow_version_id": 2 }')
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('rule_type')->badge(),
            Tables\Columns\TextColumn::make('updated_at')->since(),
        ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }
}
