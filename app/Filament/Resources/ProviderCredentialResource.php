<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProviderCredentialResource\Pages;
use App\Models\Provider;
use App\Models\ProviderCredential;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Crypt;

class ProviderCredentialResource extends Resource
{
    protected static ?string $model = ProviderCredential::class;

    protected static ?string $navigationGroup = 'Directory';

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Credentials';

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'provider-credentials';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Credential')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('provider_id')
                        ->label('Provider')
                        ->relationship('provider', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\TextInput::make('key_name')
                        ->label('Key name')
                        ->placeholder('api_key, catalog_id, ...')
                        ->required()
                        ->maxLength(255),

                    // We never bind secret_encrypted directly. Admin types a new secret here.
                    Forms\Components\TextInput::make('secret')
                        ->label('Secret (write-only)')
                        ->password()
                        ->revealable()
                        ->helperText('Leave blank to keep the existing stored secret.')
                        ->dehydrated(false), // don’t save this field directly

                    Forms\Components\KeyValue::make('meta')
                        ->label('Meta (optional)')
                        ->addButtonLabel('Add row')
                        ->keyLabel('Key')
                        ->valueLabel('Value')
                        ->nullable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('provider.name')
                    ->label('Provider')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('key_name')
                    ->label('Key')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('secret_encrypted')
                    ->label('Secret')
                    ->formatStateUsing(fn () => '•••••• (hidden)')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider_id')
                    ->label('Provider')
                    ->options(Provider::orderBy('name')->pluck('name', 'id')->all()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('rotateSecret')
                    ->label('Rotate Secret')
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        Forms\Components\TextInput::make('new_secret')
                            ->label('New secret')
                            ->password()
                            ->revealable()
                            ->required(),
                    ])
                    ->action(function (ProviderCredential $record, array $data) {
                        $record->update([
                            'secret_encrypted' => Crypt::encryptString($data['new_secret']),
                        ]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Rotate secret'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProviderCredentials::route('/'),
            'create' => Pages\CreateProviderCredential::route('/create'),
            'view' => Pages\ViewProviderCredential::route('/{record}'),
            'edit' => Pages\EditProviderCredential::route('/{record}/edit'),
        ];
    }
}
