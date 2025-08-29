<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceTypeResource\Pages;
use App\Models\ServiceType;
use App\Services\Meta\MetaMessageTemplateService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceTypeResource extends Resource
{
    protected static ?string $model = ServiceType::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Directory';

    protected static ?int $navigationSort = 10;

    public static function getLabel(): ?string
    {
        return 'Service Type';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Service Types';
    }

    public static function getSlug(): string
    {
        return 'service-types';
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

            Forms\Components\Section::make('Default Flow')
                ->schema([
                    Forms\Components\Select::make('default_flow_template_id')
                        ->label('Default Flow Template')
                        ->relationship('defaultFlowTemplate', 'name')
                        ->searchable()
                        ->helperText('This flow template will be cloned for new providers of this type.'),
                    Forms\Components\TagsInput::make('categories')
                        ->label('Meta Flow Categories')
                        ->helperText('The business categories for this flow (e.g., UTILITY, MARKETING).')
                        ->required(),
                ]),

            Forms\Components\Section::make('Custom Attributes')
                ->description('Define custom data fields that can be used in flows for this service type.')
                ->schema([
                    Forms\Components\Repeater::make('meta.custom_attributes')
                        ->label('Attributes')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Attribute Name')
                                ->helperText('e.g., patient_id, order_preference')
                                ->required(),
                            Forms\Components\Select::make('type')
                                ->options([
                                    'text' => 'Text',
                                    'number' => 'Number',
                                    'boolean' => 'Yes/No',
                                ])
                                ->default('text')
                                ->required(),
                            Forms\Components\TextInput::make('label')
                                ->label('Label')
                                ->helperText('User-friendly name for this attribute.')
                                ->required(),
                            Forms\Components\Toggle::make('required'),
                        ])
                        ->columns(2)
                        ->defaultItems(0),
                ]),

            Forms\Components\Section::make('Meta Message Templates')
                ->description('Define templates for Meta approval. Use {{1}}, {{2}} for variables in the body.')
                ->schema([
                    Forms\Components\Repeater::make('message_templates')
                        ->label('Templates')
                        ->schema([
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Template Name')
                                    ->helperText('Lowercase and underscores only, e.g., order_confirmation.')
                                    ->required(),
                                Forms\Components\TextInput::make('language')
                                    ->label('Language Code')
                                    ->helperText('e.g., en_US, ar')
                                    ->required(),
                                Forms\Components\Select::make('category')
                                    ->options([
                                        'TRANSACTIONAL' => 'Transactional',
                                        'MARKETING' => 'Marketing',
                                        'AUTHENTICATION' => 'Authentication',
                                    ])
                                    ->required(),
                            ]),
                            Forms\Components\Repeater::make('components')
                                ->label('Components')
                                ->schema([
                                    Forms\Components\Select::make('type')
                                        ->options(['HEADER' => 'Header', 'BODY' => 'Body', 'FOOTER' => 'Footer', 'BUTTONS' => 'Buttons'])
                                        ->required()
                                        ->live(),
                                    Forms\Components\Select::make('format')
                                        ->label('Header Format')
                                        ->options(['TEXT' => 'Text', 'IMAGE' => 'Image', 'DOCUMENT' => 'Document', 'VIDEO' => 'Video'])
                                        ->visible(fn ($get) => $get('type') === 'HEADER'),
                                    Forms\Components\Textarea::make('text')
                                        ->label('Content')
                                        ->helperText('For BODY, use variables like {{1}}, {{2}}.')
                                        ->visible(fn ($get) => in_array($get('type'), ['HEADER', 'BODY', 'FOOTER'])),
                                    Forms\Components\Repeater::make('buttons')
                                        ->label('Buttons')
                                        ->visible(fn ($get) => $get('type') === 'BUTTONS')
                                        ->schema([
                                            Forms\Components\Select::make('type')
                                                ->options(['QUICK_REPLY' => 'Quick Reply', 'URL' => 'URL'])
                                                ->required(),
                                            Forms\Components\TextInput::make('text')
                                                ->label('Button Text')
                                                ->required(),
                                            Forms\Components\TextInput::make('url')
                                                ->label('URL')
                                                ->helperText('Required for URL buttons.')
                                                ->visible(fn ($get) => $get('type') === 'URL'),
                                        ])
                                        ->maxItems(3),
                                ])
                                ->required(),
                        ])
                        ->columns(1)
                        ->addAction(function (\Filament\Forms\Components\Actions\Action $action, \Filament\Forms\Get $get, $state) {
                            return $action
                                ->label('Push to Meta')
                                ->icon('heroicon-o-paper-airplane')
                                ->requiresConfirmation()
                                ->form([
                                    Forms\Components\Select::make('provider_id')
                                        ->label('Provider to use for API credentials')
                                        ->options(function () use ($get) {
                                            $serviceTypeId = $get('id');
                                            return \App\Models\Provider::where('service_type_id', $serviceTypeId)->pluck('name', 'id');
                                        })
                                        ->required(),
                                ])
                                ->action(function (array $data, MetaMessageTemplateService $templateService) use ($state) {
                                    try {
                                        $provider = \App\Models\Provider::find($data['provider_id']);
                                        $response = $templateService->create($state, $provider);

                                        Notification::make()
                                            ->title('Template Submitted to Meta')
                                            ->body('Successfully submitted template `' . ($state['name'] ?? '') . '` for approval.')
                                            ->success()
                                            ->send();
                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Failed to Submit Template')
                                            ->body($e->getMessage())
                                            ->danger()
                                            ->send();
                                    }
                                });
                        }),
                ]),
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
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListServiceTypes::route('/'),
            'create' => Pages\CreateServiceType::route('/create'),
            'edit' => Pages\EditServiceType::route('/{record}/edit'),
        ];
    }
}
