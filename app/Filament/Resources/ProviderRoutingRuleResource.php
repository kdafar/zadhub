<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProviderRoutingRuleResource\Pages;
use App\Models\FlowTemplate;
use App\Models\FlowVersion;
use App\Models\Provider;
use App\Models\ProviderRoutingRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProviderRoutingRuleResource extends Resource
{
    protected static ?string $model = ProviderRoutingRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'Flows';

    protected static ?string $navigationLabel = 'Routing Rules';

    protected static ?int $navigationSort = 50;

    protected static ?string $slug = 'provider-routing-rules';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Rule')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('provider_id')
                        ->label('Provider')
                        ->options(Provider::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->required(),

                    Forms\Components\Select::make('rule_type')
                        ->label('Type')
                        ->options([
                            'fixed' => 'Fixed (always send to a specific flow)',
                            'last_used' => 'Last used (resume the last flow for this user)',
                            'nearest' => 'Nearest (choose by geo/context)',
                            'custom' => 'Custom (handler class)',
                        ])
                        ->native(false)
                        ->live()
                        ->required(),

                    // ---------- FIXED ----------
                    Forms\Components\Fieldset::make('Fixed routing')
                        ->visible(fn (Get $get) => $get('rule_type') === 'fixed')
                        ->columns(2)
                        ->schema([
                            Forms\Components\Select::make('rule_config.target_flow_template_id')
                                ->label('Target Flow Template')
                                ->options(FlowTemplate::query()->orderBy('name')->pluck('name', 'id'))
                                ->native(false)
                                ->searchable()
                                ->live()
                                ->required(fn (Get $get) => $get('rule_type') === 'fixed')
                                ->helperText('Required. The flow template to route to.'),

                            Forms\Components\Select::make('rule_config.target_flow_version_id')
                                ->label('Target Flow Version')
                                ->options(function (Get $get) {
                                    $tplId = $get('rule_config.target_flow_template_id');
                                    if (! $tplId) {
                                        return [];
                                    }

                                    return FlowVersion::query()
                                        ->where('flow_template_id', $tplId)
                                        ->orderByDesc('is_stable')
                                        ->orderByDesc('version')
                                        ->get()
                                        ->mapWithKeys(fn ($v) => [$v->id => 'v'.$v->version.($v->is_stable ? ' • stable' : '')])
                                        ->all();
                                })
                                ->native(false)
                                ->searchable()
                                ->helperText('Optional. If empty, your runtime can pick the latest stable.')
                                ->visible(fn (Get $get) => (bool) $get('rule_config.target_flow_template_id')),

                            Forms\Components\TextInput::make('rule_config.fallback')
                                ->label('Fallback strategy')
                                ->placeholder('latest_stable | latest_any | none')
                                ->helperText('Optional. If the specified version is unavailable.'),
                        ]),

                    // ---------- LAST_USED ----------
                    Forms\Components\Fieldset::make('Last used routing')
                        ->visible(fn (Get $get) => $get('rule_type') === 'last_used')
                        ->schema([
                            Forms\Components\Placeholder::make('last_used_hint')
                                ->content('No extra config needed. The engine resumes the last successfully used flow for the same WhatsApp session/customer.'),
                        ])
                        ->columnSpanFull(),

                    // ---------- NEAREST ----------
                    Forms\Components\Fieldset::make('Nearest routing')
                        ->visible(fn (Get $get) => $get('rule_type') === 'nearest')
                        ->columns(2)
                        ->schema([
                            Forms\Components\TextInput::make('rule_config.criteria')
                                ->label('Criteria')
                                ->placeholder('city_id | branch_id | geo_radius_km')
                                ->helperText('Describe how to select the nearest flow/branch (e.g., "city_id" from session state).'),

                            Forms\Components\TextInput::make('rule_config.geo_radius_km')
                                ->label('Geo radius (km)')
                                ->numeric()
                                ->placeholder('5'),

                            Forms\Components\TextInput::make('rule_config.fallback')
                                ->label('Fallback strategy')
                                ->placeholder('fixed | none')
                                ->helperText('If no nearby match is found. If "fixed", also set the fields in the Fixed section.'),
                        ]),

                    // ---------- CUSTOM ----------
                    Forms\Components\Fieldset::make('Custom handler')
                        ->visible(fn (Get $get) => $get('rule_type') === 'custom')
                        ->columns(2)
                        ->schema([
                            Forms\Components\TextInput::make('rule_config.handler_class')
                                ->label('Handler class')
                                ->placeholder('App\\Routing\\MyCustomRouter')
                                ->helperText('Must implement something like: resolve(ProviderRoutingRule $rule, WhatsappSession $session): FlowVersion'),

                            Forms\Components\KeyValue::make('rule_config.params')
                                ->label('Custom params')
                                ->keyLabel('param')
                                ->valueLabel('value')
                                ->addButtonLabel('Add param')
                                ->default([])
                                ->columnSpanFull(),
                        ]),
                ]),

            Forms\Components\Section::make('Raw config (optional)')
                ->collapsible()
                ->schema([
                    Forms\Components\KeyValue::make('rule_config')
                        ->label('rule_config JSON')
                        ->helperText('You can also edit the JSON directly. The type-specific fields above write into the same structure.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('provider.name')
                    ->label('Provider')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('rule_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'fixed' => 'primary',
                        'last_used' => 'success',
                        'nearest' => 'warning',
                        'custom' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('summary')
                    ->label('Summary')
                    ->getStateUsing(function ($record) {
                        $cfg = $record->rule_config ?? [];

                        return match ($record->rule_type) {
                            'fixed' => sprintf(
                                'template #%s, version #%s',
                                $cfg['target_flow_template_id'] ?? '—',
                                $cfg['target_flow_version_id'] ?? 'auto'
                            ),
                            'nearest' => sprintf(
                                'criteria=%s, radius=%s',
                                $cfg['criteria'] ?? '—',
                                $cfg['geo_radius_km'] ?? '—'
                            ),
                            'custom' => $cfg['handler_class'] ?? 'custom handler',
                            default => '—',
                        };
                    })
                    ->limit(80)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider_id')
                    ->label('Provider')
                    ->options(Provider::query()->orderBy('name')->pluck('name', 'id')),

                Tables\Filters\SelectFilter::make('rule_type')
                    ->label('Type')
                    ->options([
                        'fixed' => 'Fixed',
                        'last_used' => 'Last used',
                        'nearest' => 'Nearest',
                        'custom' => 'Custom',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProviderRoutingRules::route('/'),
            'create' => Pages\CreateProviderRoutingRule::route('/create'),
            'view' => Pages\ViewProviderRoutingRule::route('/{record}'),
            'edit' => Pages\EditProviderRoutingRule::route('/{record}/edit'),
        ];
    }
}
