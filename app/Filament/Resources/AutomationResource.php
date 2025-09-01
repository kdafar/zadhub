<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AutomationResource\Pages;
use App\Models\Automation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AutomationResource extends Resource
{
    protected static ?string $model = Automation::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationGroup = 'Automations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('name')
                            ->required(),
                        Forms\Components\TextInput::make('trigger_event')
                            ->required()
                            ->helperText('e.g., `order_created`. This is the event name your system will use to trigger this automation via API.'),
                    ]),
                    Forms\Components\Select::make('provider_id')
                        ->relationship('provider', 'name')
                        ->required(),
                    Forms\Components\Toggle::make('is_active')
                        ->required(),
                ])->columns(1),

                Forms\Components\Section::make('Process Builder')
                    ->description('Define the sequence of actions for this automation.')
                    ->schema([
                        Forms\Components\Repeater::make('steps')
                            ->relationship()
                            ->label('Steps')
                            ->schema([
                                Forms\Components\TextInput::make('delay_minutes')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Wait this many minutes before executing the action.')
                                    ->required(),
                                Forms\Components\Repeater::make('conditions')
                                    ->label('Conditions (optional)')
                                    ->helperText('This step will only run if ALL of these conditions are met.')
                                    ->schema([
                                        Forms\Components\TextInput::make('data_key')->label('Data Key')->helperText('e.g., order.total or customer.tags')->required(),
                                        Forms\Components\Select::make('operator')->options([
                                            'eq' => 'Equals',
                                            'neq' => 'Does not equal',
                                            'gt' => 'Greater than',
                                            'lt' => 'Less than',
                                            'gte' => 'Greater than or equal to',
                                            'lte' => 'Less than or equal to',
                                            'contains' => 'Contains',
                                            'not_contains' => 'Does not contain',
                                        ])->required(),
                                        Forms\Components\TextInput::make('value')->label('Value')->required(),
                                    ])->columns(3),
                                Forms\Components\Select::make('action_type')
                                    ->options([
                                        'send_message_template' => 'Send Message Template',
                                        'start_flow' => 'Start a Flow',
                                        'api_call' => 'API Call',
                                    ])
                                    ->live()
                                    ->required(),
                                Forms\Components\Group::make()
                                    ->schema(function (Forms\Get $get) {
                                        if ($get('action_type') === 'send_message_template') {
                                            return [
                                                Forms\Components\Select::make('action_config.template_name')
                                                    ->label('Template Name')
                                                    ->options(function (Forms\Get $get) {
                                                        // This is complex to solve dynamically here. For now, free text.
                                                        // A better solution would involve a custom field or live-updating options.
                                                        return [];
                                                    })
                                                    ->helperText('Enter the exact name of the approved Meta template.')
                                                    ->searchable()
                                                    ->required(),
                                                Forms\Components\KeyValue::make('action_config.variables')
                                                    ->label('Template Variables')
                                                    ->helperText('Map API data to template placeholders. Key = placeholder, Value = API data key. e.g., 1 => order.id'),
                                            ];
                                        } elseif ($get('action_type') === 'start_flow') {
                                            return [
                                                Forms\Components\Select::make('action_config.flow_id')
                                                    ->label('Flow to Start')
                                                    ->options(function (Forms\Get $get) {
                                                        $providerId = $get('../../provider_id');
                                                        if (! $providerId) {
                                                            return [];
                                                        }

                                                        return \App\Models\Flow::where('provider_id', $providerId)->pluck('name', 'id');
                                                    })
                                                    ->required(),
                                            ];
                                        } elseif ($get('action_type') === 'api_call') {
                                            return [
                                                Forms\Components\TextInput::make('action_config.url')->label('URL')->required(),
                                                Forms\Components\Select::make('action_config.method')->options(['GET', 'POST', 'PUT', 'DELETE'])->default('POST'),
                                                Forms\Components\KeyValue::make('action_config.headers')->label('Headers'),
                                                Forms\Components\KeyValue::make('action_config.body')->label('Body/Payload'),
                                            ];
                                        }

                                        return [];
                                    })
                                    ->columns(1),
                            ])
                            ->orderColumn('order')
                            ->defaultItems(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('provider.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('trigger_event')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAutomations::route('/'),
            'create' => Pages\CreateAutomation::route('/create'),
            'edit' => Pages\EditAutomation::route('/{record}/edit'),
        ];
    }
}
