<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FlowTemplateResource\Pages;
use App\Filament\Resources\FlowTemplateResource\RelationManagers\FlowVersionsRelationManager;
use App\Models\FlowTemplate;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FlowTemplateResource extends Resource
{
    protected static ?string $model = FlowTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $navigationGroup = 'Flows';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'flow-templates';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Template')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('service_id')
                        ->label('Service')
                        ->options(Service::query()->orderBy('name_en')->pluck('name_en', 'id'))
                        ->searchable()
                        ->required(),
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('slug')
                        ->unique(ignoreRecord: true)
                        ->required()
                        ->maxLength(255),
                ]),

            Forms\Components\Section::make('Description')
                ->schema([
                    Forms\Components\TextInput::make('description')
                        ->maxLength(255),
                ]),

            Forms\Components\Section::make('Latest Version')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('latest_version_id')
                        ->label('Latest version (for display)')
                        ->native(false)
                        ->options(fn ($record) => $record
                            ? $record->versions()
                                ->orderByDesc('is_stable')
                                ->orderByDesc('version')
                                ->get()
                                ->mapWithKeys(fn ($v) => [$v->id => 'v'.$v->version.($v->is_stable ? ' • stable' : '')])
                                ->all()
                            : []
                        )
                        ->helperText('Optional: points to the version you consider latest for UI.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('service.name_en')->label('Service')->sortable(),
                Tables\Columns\TextColumn::make('slug')->toggleable(),
                Tables\Columns\TextColumn::make('latestVersion.version')
                    ->label('Latest')
                    ->getStateUsing(fn ($record) => $record->latestVersion
                            ? 'v'.$record->latestVersion->version.($record->latestVersion->is_stable ? ' • stable' : '')
                            : '—'
                    ),

                Tables\Columns\TextColumn::make('updated_at')->since()->label('Updated'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            FlowVersionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFlowTemplates::route('/'),
            'create' => Pages\CreateFlowTemplate::route('/create'),
            'view' => Pages\ViewFlowTemplate::route('/{record}'),
            'edit' => Pages\EditFlowTemplate::route('/{record}/edit'),
        ];
    }
}
