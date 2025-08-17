<?php

namespace App\Filament\Resources\ProviderResource\RelationManagers;

use App\Models\FlowVersion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProviderFlowOverridesRelationManager extends RelationManager
{
    protected static string $relationship = 'flowOverrides'; // Provider::flowOverrides()

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('flow_version_id')
                ->label('Flow version')
                ->options(
                    FlowVersion::query()
                        ->with('flowTemplate')
                        ->get()
                        ->mapWithKeys(fn ($v) => [
                            $v->id => ($v->flowTemplate?->name ?? 'Template').' — v'.$v->version.($v->is_stable ? ' • stable' : ''),
                        ])
                        ->all()
                )
                ->searchable()
                ->required(),

            Forms\Components\Textarea::make('overrides_json')
                ->rows(10)
                ->helperText('JSON of provider-specific overrides (branding, copy, limits, etc.)')
                ->required()
                ->afterStateUpdated(function ($state, callable $set) {
                    // optional validation hook
                }),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('flowVersion.flowTemplate.name')->label('Template'),
                Tables\Columns\TextColumn::make('flowVersion.version')->label('Version')->formatStateUsing(fn ($s, $r) => 'v'.$s.($r->flowVersion?->is_stable ? ' • stable' : '')),
                Tables\Columns\TextColumn::make('updated_at')->since()->label('Updated'),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
