<?php

namespace App\FlowComponents;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class Image extends FlowComponent
{
    public static function getName(): string
    {
        return 'Image';
    }

    public static function getKey(): string
    {
        return 'image';
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('src')
                ->label('Image URL')
                ->helperText('The public URL of the image to display.')
                ->required()
                ->url(),
            Select::make('height')
                ->label('Image Height')
                ->options([
                    'small' => 'Small',
                    'medium' => 'Medium',
                    'large' => 'Large',
                ])
                ->default('medium'),
        ];
    }

    public static function getValidationRules(array $componentData): array
    {
        return []; // No user input to validate
    }
}
