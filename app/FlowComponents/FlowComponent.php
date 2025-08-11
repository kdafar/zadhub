<?php

namespace App\FlowComponents;

abstract class FlowComponent
{
    /**
     * Get the display-friendly name for the component in the builder.
     */
    abstract public static function getName(): string;

    /**
     * Get the unique key used to identify the component type.
     */
    abstract public static function getKey(): string;

    /**
     * Get the Filament form schema for configuring this component's properties.
     */
    abstract public static function getSchema(): array;

    /**
     * Get the component's type key for the final WhatsApp JSON output.
     * (e.g., "TextBody", "Dropdown", "TextInput")
     */
    public static function getJsonType(): string
    {
        // By default, we can generate this from the class name, but it can be overridden.
        return (new \ReflectionClass(static::class))->getShortName();
    }

    /**
     * Get the Laravel validation rules for this component's data.
     */
    abstract public static function getValidationRules(array $componentData): array;
}
