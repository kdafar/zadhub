<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProviderResource\Pages;
use App\Filament\Resources\ProviderResource\RelationManagers\ProviderCredentialsRelationManager;
use App\Filament\Resources\ProviderResource\RelationManagers\ProviderFlowOverridesRelationManager;
use App\Filament\Resources\ProviderResource\RelationManagers\ProviderFlowPinsRelationManager;
use App\Filament\Resources\ProviderResource\RelationManagers\ProviderHealthChecksRelationManager;
use App\Filament\Resources\ProviderResource\RelationManagers\ProviderRateLimitsRelationManager;
use App\Filament\Resources\ProviderResource\RelationManagers\ProviderRoutingRulesRelationManager;
use App\Filament\Resources\ProviderResource\RelationManagers\ProviderWebhookLogsRelationManager;
use App\Models\Provider;
use App\Models\ServiceType;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Directory';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'providers';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identity')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('service_type_id')
                        ->label('Service Type')
                        ->options(ServiceType::query()->orderBy('name_en')->pluck('name_en', 'id'))
                        ->searchable()
                        ->required(),
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255),
                ]),

            Forms\Components\Section::make('Provider Logo')
                ->schema([
                    SpatieMediaLibraryFileUpload::make('logo')
                        ->collection('logos')
                        ->image()
                        ->imageEditor(),
                ]),

            Forms\Components\Section::make('Status & Auth')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('status')
                        ->options([
                            'active' => 'Active',
                            'paused' => 'Paused',
                            'disabled' => 'Disabled',
                        ])
                        ->required(),
                    Forms\Components\Toggle::make('is_sandbox')
                        ->label('Sandbox?')
                        ->inline(false),
                    Forms\Components\Select::make('auth_type')
                        ->options([
                            'none' => 'None',
                            'apikey' => 'API key',
                            'bearer' => 'Bearer token',
                        ])
                        ->required(),
                ]),

            Forms\Components\Section::make('Connectivity')
                ->columns(1)
                ->schema([
                    Forms\Components\TextInput::make('api_base_url')
                        ->url()
                        ->maxLength(255)
                        ->helperText('Base URL of the provider API (e.g., https://api.vendor.com)'),
                ]),

            Forms\Components\Section::make('WhatsApp Credentials')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('whatsapp_phone_number_id')
                        ->label('WhatsApp Phone Number ID'),
                    Forms\Components\TextInput::make('meta.waba_id')
                        ->label('WhatsApp Business Account ID (WABA ID)'),
                    Forms\Components\TextInput::make('api_token')
                        ->label('API Token')
                        ->password()
                        ->revealable(),
                ]),

            Forms\Components\Section::make('Configuration')
                ->columns(1)
                ->schema([
                    Forms\Components\KeyValue::make('locale_defaults')
                        ->addButtonLabel('Add default')
                        ->helperText('Per-locale defaults (e.g., ar => {"greeting":"..."}).'),
                    Forms\Components\KeyValue::make('feature_flags')
                        ->addButtonLabel('Add flag')
                        ->helperText('Feature switches per provider.'),
                    Forms\Components\KeyValue::make('meta')
                        ->label('Meta Configuration')
                        ->addButtonLabel('Add Meta Field')
                        ->helperText('Provider-specific settings like app_secret, verify_token, flow IDs, etc.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('logo')
                    ->collection('logos')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('serviceType.name_en')
                    ->label('Service Type')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'paused',
                        'danger' => 'disabled',
                    ])
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_sandbox')
                    ->boolean()
                    ->label('Sandbox'),
                Tables\Columns\TextColumn::make('auth_type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('api_base_url')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->since()
                    ->label('Updated'),
            ])
            ->filters([
                SelectFilter::make('service_type_id')
                    ->label('Service Type')
                    ->relationship('serviceType', 'name_en')
                    ->searchable(),
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'paused' => 'Paused',
                        'disabled' => 'Disabled',
                    ]),
            ])
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
            ProviderCredentialsRelationManager::class,
            ProviderFlowPinsRelationManager::class,
            ProviderFlowOverridesRelationManager::class,
            ProviderRoutingRulesRelationManager::class,
            ProviderHealthChecksRelationManager::class,
            ProviderRateLimitsRelationManager::class,
            ProviderWebhookLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProviders::route('/'),
            'create' => Pages\CreateProvider::route('/create'),
            'view' => Pages\ViewProvider::route('/{record}'),
            'edit' => Pages\EditProvider::route('/{record}/edit'),
        ];
    }
}
