<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FlowTemplateResource\Pages;
use App\Models\FlowTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FlowTemplateResource extends Resource
{
    protected static ?string $model = FlowTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Flow Management';

    protected static ?string $modelLabel = 'Flow Template';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('service_id')
                    ->relationship('service', 'name')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->helperText('e.g., "Standard Restaurant Order", "New Patient Intake"')
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('service.name')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('builder')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->url(fn (FlowTemplate $record): string => static::getUrl('build', ['record' => $record])),
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
            'index' => Pages\ListFlowTemplates::route('/'),
            'create' => Pages\CreateFlowTemplate::route('/create'),
            'edit' => Pages\EditFlowTemplate::route('/{record}/edit'),
            'build' => Pages\BuildTemplate::route('/{record}/build'),
        ];
    }
}
