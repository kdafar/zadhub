<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CartResource\Pages;
use App\Models\Cart;
use App\Models\WhatsappSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CartResource extends Resource
{
    protected static ?string $model = Cart::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Commerce';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'carts';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Cart')
                ->columns(3)
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

                    Forms\Components\TextInput::make('currency')
                        ->maxLength(8)->default('KWD')->required(),

                    Forms\Components\Textarea::make('meta')
                        ->rows(10)
                        ->helperText('JSON meta blob.')
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
            Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
            Tables\Columns\TextColumn::make('whatsappSession.customer_phone_number')->label('Phone')->sortable(),
            Tables\Columns\TextColumn::make('currency')->sortable(),
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
            'index' => Pages\ListCarts::route('/'),
            'view' => Pages\ViewCart::route('/{record}'),
            'edit' => Pages\EditCart::route('/{record}/edit'),
        ];
    }
}
