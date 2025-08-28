<?php

namespace App\FlowComponents;

use Filament\Forms\Components\TextInput;

class Document extends FlowComponent
{
    public static function getName(): string
    {
        return 'Document';
    }

    public static function getKey(): string
    {
        return 'document';
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('src')
                ->label('Document URL')
                ->helperText('The public URL of the document (e.g., a PDF).')
                ->required()
                ->url(),
            TextInput::make('filename')
                ->label('Filename')
                ->helperText('The filename to display to the user (e.g., invoice.pdf).')
                ->required(),
            TextInput::make('caption')
                ->label('Caption'),
        ];
    }

    public static function getValidationRules(array $componentData): array
    {
        return []; // No user input to validate
    }
}
