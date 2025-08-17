<?php

namespace App\Filament\Resources\ProviderResource\RelationManagers;

use App\Models\FlowTemplate;
use App\Models\FlowVersion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProviderFlowPinsRelationManager extends RelationManager
{
    protected static string $relationship = 'flowPins'; // Provider::flowPins()

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('flow_template_id')
                ->label('Flow template')
                ->options(FlowTemplate::query()->orderBy('name')->pluck('name', 'id'))
                ->required()
                ->reactive(),

            Forms\Components\Select::make('pinned_version_id')
                ->label('Pinned version')
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
                        ->mapWithKeys(fn ($v) => [$v->id => 'v'.$v->version.($v->is_stable ? ' • stable' : '')])
                        ->all();
                })
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('flowTemplate.name')->label('Template'),
                Tables\Columns\TextColumn::make('pinnedVersion.version')->label('Version')->formatStateUsing(fn ($state, $record) => 'v'.$state.($record->pinnedVersion?->is_stable ? ' • stable' : '')),
                Tables\Columns\TextColumn::make('updated_at')->since()->label('Updated'),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
