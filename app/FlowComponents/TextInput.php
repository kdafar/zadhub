<?php

namespace App\FlowComponents;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput as FilamentTextInput;

class TextInput extends FlowComponent
{
    public static function getName(): string
    {
        return 'Text Input';
    }

    public static function getKey(): string
    {
        return 'text_input';
    }

    public static function getSchema(): array
    {
        return [
            FilamentTextInput::make('name')->label('Variable Name')->required()
                ->helperText('The user\'s input will be saved with this key (e.g., "customer_name").'),
            FilamentTextInput::make('label')->label('Input Field Label')->required(),
            Select::make('input_type')->options([
                'text' => 'Text',
                'email' => 'Email',
                'phone' => 'Phone',
                'number' => 'Number',
            ])->default('text'),
            Checkbox::make('is_required')->label('Required'),
        ];
    }

    public static function getValidationRules(array $componentData): array
    {
        $rules = [];
        if ($componentData['is_required'] ?? false) {
            $rules[] = 'required';
        }

        switch ($componentData['input_type'] ?? 'text') {
            case 'email':
                $rules[] = 'email';
                break;
            case 'number':
                $rules[] = 'numeric';
                break;
        }

        // Return rules in Laravel format: ['variable_name' => ['required', 'email']]
        return [$componentData['name'] => $rules];
    }
}
