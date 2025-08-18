<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FlowVersionResource\Pages;
use App\Models\FlowVersion;
use App\Models\MetaFlow;
use App\Models\Provider;
use App\Models\ServiceType;
use App\Services\Meta\MetaFlowsService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;

class FlowVersionResource extends Resource
{
    protected static ?string $model = FlowVersion::class;

    protected static ?string $navigationGroup = 'WhatsApp';

    protected static ?string $navigationLabel = 'Flow Versions';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\TextInput::make('name')->maxLength(120),
                Forms\Components\Select::make('service_type_id')->label('Service Type')
                    ->options(ServiceType::query()->pluck('name', 'id'))->searchable(),
                Forms\Components\Select::make('provider_id')->label('Provider')
                    ->options(Provider::query()->pluck('name', 'id'))->searchable(),
            ]),
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\TextInput::make('version')->numeric()->minValue(1)->default(1),
                Forms\Components\TextInput::make('status')->disabled(),
                Forms\Components\DateTimePicker::make('published_at')->disabled(),
            ]),
            Forms\Components\Textarea::make('definition')
                ->rows(28)
                ->required()
                ->helperText('JSON with screens[] and meta.start'),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('name')->searchable()->limit(30),
            Tables\Columns\TextColumn::make('serviceType.name')->label('Service Type')->sortable(),
            Tables\Columns\TextColumn::make('provider.name')->label('Provider')->sortable(),
            Tables\Columns\BadgeColumn::make('status')->colors([
                'warning' => 'draft',
                'success' => 'published',
                'gray' => 'archived',
            ])->sortable(),
            Tables\Columns\TextColumn::make('version')->sortable(),
            Tables\Columns\TextColumn::make('published_at')->dateTime()->sortable(),
            Tables\Columns\TextColumn::make('updated_at')->dateTime()->label('Updated'),
        ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-m-arrow-up-circle')
                    ->color('success')
                    ->visible(fn (FlowVersion $record) => $record->status !== 'published')
                    ->requiresConfirmation()
                    ->action(fn (FlowVersion $record) => $record->publish()),
                Tables\Actions\Action::make('unpublish')
                    ->label('Unpublish')
                    ->icon('heroicon-m-arrow-down-circle')
                    ->color('warning')
                    ->visible(fn (FlowVersion $record) => $record->status === 'published')
                    ->requiresConfirmation()
                    ->action(fn (FlowVersion $record) => $record->unpublish()),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('publishToMeta')
                    ->label('Publish to Meta')
                    ->icon('heroicon-m-paper-airplane')
                    ->requiresConfirmation()
                    ->action(function (\App\Models\FlowVersion $record, MetaFlowsService $meta) {
                        // 1) Create (or reuse) draft on Meta
                        $mf = MetaFlow::where('flow_version_id', $record->id)->first();
                        if (! $mf || ! $mf->meta_flow_id) {
                            $mf = $meta->create($record);
                        }
                        // 2) Publish on Meta
                        if ($mf->status !== 'published') {
                            $meta->publish($mf);
                        }

                        Notification::make()
                            ->title('Flow published on Meta')
                            ->body("Meta Flow ID: {$mf->meta_flow_id}")
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListFlowVersions::route('/'),
            'create' => Pages\CreateFlowVersion::route('/create'),
            'edit' => Pages\EditFlowVersion::route('/{record}/edit'),
        ];
    }
}
