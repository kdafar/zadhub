<?php

namespace App\Filament\Resources\ServiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceKeywordsRelationManager extends RelationManager
{
    protected static string $relationship = 'keywords';

    protected static ?string $title = 'Keywords';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('keyword')->required()->maxLength(100),
            Forms\Components\Select::make('locale')
                ->options([
                    'en' => 'English',
                    'ar' => 'Arabic',
                ])->default('en')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('keyword')->searchable()->sortable(),
            Tables\Columns\BadgeColumn::make('locale'),
            Tables\Columns\TextColumn::make('created_at')->since()->sortable(),
        ])->headerActions([
            Tables\Actions\CreateAction::make(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }
}
