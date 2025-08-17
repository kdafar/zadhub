<?php

namespace App\Filament\Resources\ProviderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Crypt;

class ProviderCredentialsRelationManager extends RelationManager
{
    protected static string $relationship = 'credentials'; // ensure Provider::credentials() exists

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('key_name')
                ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->where('provider_id', $this->ownerRecord->id))
                ->required()
                ->maxLength(255),

            // We take a plaintext here and encrypt into secret_encrypted.
            Forms\Components\TextInput::make('secret_plain')
                ->label('Secret (plain)')
                ->password()
                ->revealable()
                ->dehydrated(false)
                ->required(fn ($context) => $context === 'create')
                ->helperText('This will be encrypted and stored as secret_encrypted.'),

            Forms\Components\KeyValue::make('meta')
                ->helperText('Optional metadata (e.g., {"is_secret":true})'),
        ])->mutateFormDataUsing(function (array $data): array {
            if (! empty($data['secret_plain'])) {
                $data['secret_encrypted'] = Crypt::encryptString($data['secret_plain']);
            }
            unset($data['secret_plain']);

            if (! isset($data['meta'])) {
                $data['meta'] = [];
            }
            $data['meta']['is_encrypted'] = true;

            return $data;
        });
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key_name')->searchable(),
                Tables\Columns\IconColumn::make('meta.is_encrypted')->boolean()->label('Encrypted'),
                Tables\Columns\TextColumn::make('updated_at')->since()->label('Updated'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data) => tap($data, function (&$d) {
                        unset($d['secret_encrypted']); // never hydrate encrypted value into form
                    })),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
