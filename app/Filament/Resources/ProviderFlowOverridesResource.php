<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProviderFlowOverridesResource\Pages;
use App\Models\FlowVersion;
use App\Models\Provider;
use App\Models\ProviderFlowOverride;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProviderFlowOverridesResource extends Resource
{
    protected static ?string $model = ProviderFlowOverride::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationGroup = 'Flows';

    protected static ?string $navigationLabel = 'Flow Overrides';

    protected static ?int $navigationSort = 70;

    protected static ?string $slug = 'provider-flow-overrides';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Binding')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('provider_id')
                        ->label('Provider')
                        ->native(false)
                        ->searchable()
                        ->preload()
                        ->required()
                        ->options(
                            Provider::query()->orderBy('name')->pluck('name', 'id')
                        ),

                    Forms\Components\Select::make('flow_version_id')
                        ->label('Flow Version')
                        ->native(false)
                        ->searchable()
                        ->preload()
                        ->required()
                        ->options(function () {
                            // Show "Template • vX (stable)" for clarity
                            return FlowVersion::query()
                                ->with('template:id,name')
                                ->orderByDesc('is_stable')
                                ->orderByDesc('version')
                                ->get()
                                ->mapWithKeys(function ($v) {
                                    $label = ($v->template?->name ?? 'Template').' • v'.$v->version.($v->is_stable ? ' (stable)' : '');

                                    return [$v->id => $label];
                                })
                                ->all();
                        }),
                ]),

            Forms\Components\Textarea::make('overrides_json')
                ->rows(16)
                ->required()
                ->helperText('Paste valid JSON. Example: {"branding":{"primaryColor":"#e74c3c"}}')
                ->rules(['json']) // <-- use the string rule instead
                ->dehydrateStateUsing(function ($state) {
                    if (is_array($state)) {
                        return json_encode($state);
                    }
                    json_decode($state);

                    return json_last_error() === JSON_ERROR_NONE ? $state : json_encode([]);
                })
                ->formatStateUsing(function ($state) {
                    if (is_array($state)) {
                        return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    }
                    $decoded = json_decode((string) $state, true);

                    return json_last_error() === JSON_ERROR_NONE
                        ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                        : $state;
                }),

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
                Tables\Columns\TextColumn::make('flowVersionLabel')
                    ->label('Flow Version')
                    ->state(function (ProviderFlowOverride $record) {
                        $v = $record->flowVersion()->with('template:id,name')->first();
                        if (! $v) {
                            return '—';
                        }

                        return ($v->template?->name ?? 'Template').' • v'.$v->version.($v->is_stable ? ' (stable)' : '');
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->since()->label('Updated'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider_id')
                    ->label('Provider')
                    ->options(Provider::query()->orderBy('name')->pluck('name', 'id')),
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
            'index' => Pages\ListProviderFlowOverrides::route('/'),
            'create' => Pages\CreateProviderFlowOverrides::route('/create'),
            'view' => Pages\ViewProviderFlowOverrides::route('/{record}'),
            'edit' => Pages\EditProviderFlowOverrides::route('/{record}/edit'),
        ];
    }
}
