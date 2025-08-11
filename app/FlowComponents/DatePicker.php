<?php

namespace App\FlowComponents;

use Filament\Forms\Components\TextInput;

class DatePicker extends FlowComponent
{
    public static function getName(): string
    {
        return 'Date Picker';
    }

    public static function getKey(): string
    {
        return 'date_picker';
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('name')
                ->label('Variable Name')
                ->helperText('The selected date will be saved with this key (e.g., "appointment_date").')
                ->required(),
            TextInput::make('label')
                ->label('Date Picker Label')
                ->required(),
        ];
    }

    public static function getValidationRules(array $componentData): array
    {
        if ($componentData['is_required'] ?? false) {
            return [$componentData['name'] => ['required', 'date']];
        }

        return [$componentData['name'] => ['nullable', 'date']];
    }
}
