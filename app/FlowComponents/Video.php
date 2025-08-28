<?php

namespace App\FlowComponents;

use Filament\Forms\Components\TextInput;

class Video extends FlowComponent
{
    public static function getName(): string
    {
        return 'Video';
    }

    public static function getKey(): string
    {
        return 'video';
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('src')
                ->label('Video URL')
                ->helperText('The public URL of the video file (e.g., .mp4).')
                ->required()
                ->url(),
            TextInput::make('caption')
                ->label('Caption'),
        ];
    }

    public static function getValidationRules(array $componentData): array
    {
        return []; // No user input to validate
    }
}
