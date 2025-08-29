<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentLinkResource\Pages;
use App\Models\Order;
use App\Models\PaymentLink;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentLinkResource extends Resource
{
    protected static ?string $model = PaymentLink::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationGroup = 'Commerce';

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'payment-links';

    protected static ?string $recordTitleAttribute = 'external_url';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Payment Link')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('order_id')
                        ->label('Order')
                        ->native(false)->searchable()->preload()
                        ->options(
                            Order::query()->orderByDesc('id')->get()
                                ->mapWithKeys(fn ($o) => [$o->id => ($o->external_order_id ?: ('#'.$o->id))])
                                ->all()
                        )
                        ->required(),

                    Forms\Components\TextInput::make('external_url')
                        ->label('URL')
                        ->url()
                        ->required()
                        ->maxLength(2048),

                    Forms\Components\Select::make('status')
                        ->options(['new' => 'new', 'expired' => 'expired', 'paid' => 'paid'])
                        ->native(false)
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('order.external_order_id')
                ->label('Order #')->placeholder('—')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('status')->badge()->sortable(),
            Tables\Columns\TextColumn::make('external_url')->limit(50)->copyable(),
            Tables\Columns\TextColumn::make('created_at')->since()->label('Created'),
            Tables\Columns\TextColumn::make('updated_at')->since()->label('Updated'),
        ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentLinks::route('/'),
            'view' => Pages\ViewPaymentLink::route('/{record}'),
            'edit' => Pages\EditPaymentLink::route('/{record}/edit'),
        ];
    }
}
