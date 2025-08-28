<?php

namespace App\FlowComponents;

use Filament\Forms\Components\TextInput;

class Audio extends FlowComponent
{
    public static function getName(): string
    {
        return 'Audio';
    }

    public static function getKey(): string
    {
        return 'audio';
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('src')
                ->label('Audio URL')
                ->helperText('The public URL of the audio file (e.g., .mp3, .ogg).')
                ->required()
                ->url(),
        ];
    }

    public static function getValidationRules(array $componentData): array
    {
        return []; // No user input to validate
    }
}
