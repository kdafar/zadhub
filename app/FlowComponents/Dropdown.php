<?php

namespace App\FlowComponents;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;

class Dropdown extends FlowComponent
{
    public static function getName(): string
    {
        return 'Dropdown';
    }

    public static function getKey(): string
    {
        return 'dropdown';
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('name')->label('Variable Name')->required()
                ->helperText('The user\'s selection will be saved with this key (e.g., "selected_city").'),
            TextInput::make('label')->label('Dropdown Label')->required(),
            KeyValue::make('options')->label('Options (ID => Title)')
                ->keyLabel('Value (ID)')
                ->valueLabel('Display Text (Title)')
                ->required(),
        ];
    }

    public static function getValidationRules(array $componentData): array
    {
        if ($componentData['is_required'] ?? false) {
            return [$componentData['name'] => ['required']];
        }

        return [];
    }
}
