<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LandingLeadResource\Pages;
use App\Models\LandingLead;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LandingLeadResource extends Resource
{
    protected static ?string $model = LandingLead::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox';

    protected static ?string $navigationGroup = 'CRM';

    protected static ?string $navigationLabel = 'Leads';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->disabled(),
            Forms\Components\TextInput::make('company')->disabled(),
            Forms\Components\TextInput::make('email')->disabled(),
            Forms\Components\TextInput::make('phone')->disabled(),
            Forms\Components\TextInput::make('use_case')->disabled(),
            Forms\Components\TextInput::make('locale')->disabled(),
            Forms\Components\Textarea::make('message')->rows(4)->disabled(),
            Forms\Components\KeyValue::make('utm')->disableAddingRows()->disableDeletingRows()->disabled(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            Tables\Columns\TextColumn::make('name')->searchable(),
            Tables\Columns\TextColumn::make('phone')->searchable(),
            Tables\Columns\TextColumn::make('use_case')->badge(),
            Tables\Columns\BadgeColumn::make('locale'),
            Tables\Columns\TextColumn::make('utm->source')->label('UTM Source'),
        ])
            ->filters([
                Tables\Filters\SelectFilter::make('use_case')->options([
                    'restaurant' => 'restaurant', 'pharmacy' => 'pharmacy', 'grocery' => 'grocery', 'logistics' => 'logistics', 'other' => 'other',
                ]),
                Tables\Filters\SelectFilter::make('locale')->options(['en' => 'en', 'ar' => 'ar']),
                Tables\Filters\Filter::make('date')->form([
                    Forms\Components\DatePicker::make('from'),
                    Forms\Components\DatePicker::make('to'),
                ])->query(function ($query, array $data) {
                    return $query
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
                }),
            ])
            ->actions([Tables\Actions\ViewAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLandingLeads::route('/'),
        ];
    }
}
