<?php

namespace App\FlowComponents;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;

class TextBody extends FlowComponent
{
    public static function getName(): string
    {
        return 'Text (Body)';
    }

    public static function getKey(): string
    {
        return 'text_body';
    }

    public static function getSchema(): array
    {
        return [
            Textarea::make('text')->label('Content')->required(),
            Checkbox::make('markdown')->label('Enable Markdown'),
        ];
    }

    public static function getValidationRules(array $componentData): array
    {
        return []; // No user input to validate
    }
}
