<?php

namespace App\Services;

// Components

// WA transport
// adjust the namespace if yours differs

class FlowRenderer
{
    public function __construct() {}

    /**
     * Prepares the data payload for a given screen to be sent to Meta's Flow API.
     */
    public function renderScreen(array $screenConfig, array $context = [], ?string $errorMessage = null): array
    {
        // Interpolate all string values in the screen configuration
        $interpolatedConfig = $this->interpolateArray($screenConfig, $context);

        $screenData = $interpolatedConfig['data'] ?? [];

        $title = $screenData['title'] ?? $interpolatedConfig['title'] ?? ' ';
        $body = $screenData['text'] ?? '';
        $footer = $screenData['footer_label'] ?? 'Next';

        if ($errorMessage) {
            $body = $errorMessage;
        }

        return [
            'id' => $interpolatedConfig['id'],
            'title' => $title,
            'body' => $body,
            'footer' => $footer,
            'data_bindings' => $context,
        ];
    }

    protected function interpolateArray(array $arr, array $context): array
    {
        foreach ($arr as $key => &$value) {
            if (is_array($value)) {
                $value = $this->interpolateArray($value, $context);
            } elseif (is_string($value)) {
                $value = $this->interpolate($value, $context);
            }
        }

        return $arr;
    }

    protected function interpolate(string $text, array $context): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/', function ($matches) use ($context) {
            return data_get($context, $matches[1], $matches[0]);
        }, $text);
    }
}
