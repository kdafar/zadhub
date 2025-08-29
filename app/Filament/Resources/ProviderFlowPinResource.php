<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProviderFlowPinResource\Pages;
use App\Models\FlowTemplate;
use App\Models\FlowVersion;
use App\Models\Provider;
use App\Models\ProviderFlowPin;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProviderFlowPinResource extends Resource
{
    protected static ?string $model = ProviderFlowPin::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Flows';

    protected static ?string $navigationLabel = 'Flow Pins';

    protected static ?int $navigationSort = 60;

    protected static ?string $slug = 'provider-flow-pins';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Pin flow version for a provider')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('provider_id')
                        ->label('Provider')
                        ->options(Provider::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->native(false),

                    Forms\Components\Select::make('flow_template_id')
                        ->label('Flow Template')
                        ->options(FlowTemplate::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->native(false)
                        ->live(),

                    Forms\Components\Select::make('pinned_version_id')
                        ->label('Pin to Version')
                        ->options(function (Get $get) {
                            $tplId = $get('flow_template_id');
                            if (! $tplId) {
                                return [];
                            }

                            return FlowVersion::query()
                                ->where('flow_template_id', $tplId)
                                ->orderByDesc('is_stable')
                                ->orderByDesc('version')
                                ->get()
                                ->mapWithKeys(fn ($v) => [
                                    $v->id => 'v'.$v->version.($v->is_stable ? ' • stable' : ''),
                                ])
                                ->all();
                        })
                        ->helperText('Choose the exact version to pin. Leave blank to prevent saving (required).')
                        ->required()
                        ->native(false)
                        ->searchable(),
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

                Tables\Columns\TextColumn::make('flowTemplate.name')
                    ->label('Flow Template')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('pinnedVersion.version')
                    ->label('Pinned Version')
                    ->formatStateUsing(fn ($state, $record) => $record->pinnedVersion
                        ? 'v'.$record->pinnedVersion->version.($record->pinnedVersion->is_stable ? ' • stable' : '')
                        : '—'
                    )
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
            'index' => Pages\ListProviderFlowPins::route('/'),
            'create' => Pages\CreateProviderFlowPin::route('/create'),
            'view' => Pages\ViewProviderFlowPin::route('/{record}'),
            'edit' => Pages\EditProviderFlowPin::route('/{record}/edit'),
        ];
    }
}
