<?php

namespace App\Filament\Resources\ServiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProvidersRelationManager extends RelationManager
{
    protected static string $relationship = 'providers';

    protected static ?string $title = 'Providers';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),
            Forms\Components\Select::make('status')
                ->options([
                    'active' => 'Active',
                    'paused' => 'Paused',
                    'disabled' => 'Disabled',
                ])->required(),
            Forms\Components\Select::make('auth_type')
                ->options([
                    'none' => 'None',
                    'apikey' => 'API Key',
                    'bearer' => 'Bearer Token',
                ])->required(),
            Forms\Components\Toggle::make('is_sandbox')->label('Sandbox'),
            Forms\Components\TextInput::make('api_base_url')->url(),
            Forms\Components\KeyValue::make('locale_defaults')->label('Locale Defaults')->keyLabel('key')->valueLabel('value'),
            Forms\Components\KeyValue::make('feature_flags')->label('Feature Flags')->keyLabel('flag')->valueLabel('value'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('slug')->sortable()->searchable(),
            Tables\Columns\BadgeColumn::make('status')->colors([
                'success' => 'active',
                'warning' => 'paused',
                'danger' => 'disabled',
            ]),
            Tables\Columns\IconColumn::make('is_sandbox')->boolean()->label('Sandbox'),
            Tables\Columns\TextColumn::make('updated_at')->since()->sortable(),
        ])->headerActions([
            Tables\Actions\CreateAction::make(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }
}
