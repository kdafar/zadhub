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
        $screenData = $screenConfig['data'] ?? [];

        $title = $screenData['title'] ?? $screenConfig['title'] ?? ' ';
        $body = $this->interpolate($screenData['text'] ?? '', $context);
        $footer = $screenData['footer_label'] ?? 'Next';

        if ($errorMessage) {
            $body = $errorMessage;
        }

        return [
            'id' => $screenConfig['id'],
            'title' => $this->interpolate($title, $context),
            'body' => $body,
            'footer' => $this->interpolate($footer, $context),
            'data_bindings' => $context,
        ];
    }

    protected function interpolate(string $text, array $context): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/', function ($matches) use ($context) {
            return data_get($context, $matches[1], $matches[0]);
        }, $text);
    }
}
