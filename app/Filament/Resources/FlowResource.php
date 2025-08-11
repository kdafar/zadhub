<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FlowResource\Pages;
use App\Models\Flow;
use App\Models\FlowTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FlowResource extends Resource
{
    protected static ?string $model = Flow::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Flow Management';

    protected static ?string $modelLabel = 'Provider Flow (Instance)';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('provider_id')
                    ->relationship('provider', 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('flow_template_id')
                    ->label('Template')
                    ->options(FlowTemplate::where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->helperText('Choose the master template to use for this flow.'),
                Forms\Components\TextInput::make('trigger_keyword')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->helperText('The keyword the user sends to start this flow (e.g., "hi", "order").'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('provider.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('template.name')->label('Template Used')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('trigger_keyword'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider_id')->relationship('provider', 'name')->label('Provider'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFlows::route('/'),
            'create' => Pages\CreateFlow::route('/create'),
            'edit' => Pages\EditFlow::route('/{record}/edit'),
        ];
    }
}
