<?php

namespace App\Filament\Resources\ProviderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProviderRoutingRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'routingRules'; // Provider::routingRules()

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('rule_type')
                ->options([
                    'fixed' => 'Fixed',
                    'last_used' => 'Last used',
                    'nearest' => 'Nearest',
                    'custom' => 'Custom',
                ])
                ->required()
                ->reactive(),

            // For "fixed": we'll expect config: { "flow_template_id": X, "flow_version_id": Y, "keywords": ["menu","order"] }
            Forms\Components\KeyValue::make('rule_config')
                ->addButtonLabel('Add config')
                ->helperText('JSON config. For "fixed": flow_template_id, flow_version_id, keywords[].')
                ->visible(fn (Get $get) => in_array($get('rule_type'), ['fixed', 'custom'])),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('rule_type')
                    ->colors([
                        'primary' => 'fixed',
                        'success' => 'last_used',
                        'warning' => 'nearest',
                        'gray' => 'custom',
                    ]),
                Tables\Columns\TextColumn::make('rule_config')
                    ->limit(80)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')->since()->label('Updated'),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
