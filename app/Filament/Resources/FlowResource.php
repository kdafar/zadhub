<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FlowResource\Pages;
use App\Models\Flow;
use App\Models\Provider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FlowResource extends Resource
{
    protected static ?string $model = Flow::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'Flows';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\Select::make('provider_id')
                    ->label('Provider')
                    ->options(Provider::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('trigger_keyword')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->default(true),
                Forms\Components\KeyValue::make('meta')
                    ->label('Meta Data'),
            ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('provider.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('trigger_keyword')->searchable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->since(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('build')
                    ->label('Build')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->url(fn (Flow $record) => self::getUrl('build', ['record' => $record])),
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
            'index' => Pages\ListFlows::route('/'),
            'create' => Pages\CreateFlow::route('/create'),
            'edit' => Pages\EditFlow::route('/{record}/edit'),
            'build' => Pages\BuildFlow::route('/{record}/build'),
        ];
    }
}
