<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\Provider;
use App\Models\ServiceType;
use App\Models\WhatsappSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Commerce';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'orders';

    protected static ?string $recordTitleAttribute = 'external_order_id';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Links')
                ->columns(4)
                ->schema([
                    Forms\Components\Select::make('session_id')
                        ->label('WhatsApp Session')
                        ->native(false)->searchable()->preload()
                        ->options(
                            WhatsappSession::query()->orderByDesc('id')->get()
                                ->mapWithKeys(fn ($s) => [$s->id => $s->customer_phone_number.' (#'.$s->id.')'])
                                ->all()
                        )
                        ->required(),

                    Forms\Components\Select::make('provider_id')
                        ->label('Provider')->native(false)->searchable()->preload()
                        ->options(Provider::query()->orderBy('name')->pluck('name', 'id'))->required(),

                    Forms\Components\Select::make('service_type_id')
                        ->label('Service Type')->native(false)->searchable()->preload()
                        ->options(ServiceType::query()->orderBy('name_en')->pluck('name_en', 'id'))
                        ->required(),

                    Forms\Components\TextInput::make('external_order_id')
                        ->label('External Order #')
                        ->maxLength(255),
                ]),

            Forms\Components\Section::make('Status & Amounts')
                ->columns(5)
                ->schema([
                    // free text; your table doesn't constrain enum here
                    Forms\Components\TextInput::make('status')->maxLength(64)->required(),

                    Forms\Components\TextInput::make('subtotal')->numeric()->step('0.001')->required(),
                    Forms\Components\TextInput::make('delivery_fee')->numeric()->step('0.001')->required(),
                    Forms\Components\TextInput::make('discount')->numeric()->step('0.001')->required(),
                    Forms\Components\TextInput::make('total')->numeric()->step('0.001')->required(),
                ]),

            Forms\Components\Section::make('Snapshot (JSON)')
                ->schema([
                    Forms\Components\Textarea::make('snapshot')
                        ->rows(12)
                        ->helperText('Optional order snapshot JSON.')
                        ->dehydrateStateUsing(function ($state) {
                            if (is_array($state)) {
                                return json_encode($state);
                            }
                            if (blank($state)) {
                                return null;
                            }
                            json_decode($state);

                            return json_last_error() === JSON_ERROR_NONE ? $state : json_encode([]);
                        })
                        ->formatStateUsing(function ($state) {
                            if (is_array($state)) {
                                return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                            }
                            $decoded = json_decode((string) $state, true);

                            return json_last_error() === JSON_ERROR_NONE
                                ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                                : $state;
                        }),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('external_order_id')->label('Order #')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('provider.name')->label('Provider')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('serviceType.name_en')->label('Service Type')->sortable(),
            Tables\Columns\TextColumn::make('whatsappSession.customer_phone_number')->label('Phone')->sortable(),
            Tables\Columns\TextColumn::make('status')->badge()->sortable(),
            Tables\Columns\TextColumn::make('total')->numeric(3)->sortable()->weight('bold'),
            Tables\Columns\TextColumn::make('created_at')->since()->label('Created'),
            Tables\Columns\TextColumn::make('updated_at')->since()->label('Updated')->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider_id')->label('Provider')
                    ->options(Provider::query()->orderBy('name')->pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('service_type_id')->label('Service Type')
                    ->options(ServiceType::query()->orderBy('name_en')->pluck('name_en', 'id')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
