<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers\FlowTemplatesRelationManager;
use App\Filament\Resources\ServiceResource\RelationManagers\ProvidersRelationManager;
use App\Filament\Resources\ServiceResource\RelationManagers\ServiceKeywordsRelationManager;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Directory';

    protected static ?int $navigationSort = 10;

    public static function getLabel(): ?string
    {
        return 'Service';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Services';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identity')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->label('Code')
                        ->helperText('Short machine code, e.g. restaurant / telecom / hospital')
                        ->required()
                        ->maxLength(64)
                        ->unique(ignoreRecord: true),

                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    Forms\Components\Select::make('default_locale')
                        ->options([
                            'en' => 'English',
                            'ar' => 'Arabic',
                        ])
                        ->required(),
                ]),

            Forms\Components\Section::make('Names')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name_en')->label('Name (EN)')->required(),
                    Forms\Components\TextInput::make('name_ar')->label('Name (AR)'),
                ]),

            Forms\Components\Textarea::make('description')->rows(2),

            Forms\Components\Toggle::make('is_active')
                ->default(true)
                ->inline(false),

            Forms\Components\KeyValue::make('meta')
                ->keyLabel('Key')
                ->valueLabel('Value')
                ->helperText('Stored as JSON. e.g. supports=address/menu/checkout')
                ->reorderable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->sortable()->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('slug')->sortable()->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('name_en')->label('Name')->sortable()->searchable(),
                Tables\Columns\BadgeColumn::make('default_locale')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(), // keep if your table has soft deletes; otherwise this is a hard delete
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
            FlowTemplatesRelationManager::class,
            ProvidersRelationManager::class,
            ServiceKeywordsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
