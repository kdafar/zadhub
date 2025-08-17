<?php

namespace App\Services\Flows;

use RuntimeException;

class FlowRenderer
{
    /**
     * Render a screen by its ID into a normalized message array
     * that your WhatsAppApiService can send (text, image, list, etc.)
     */
    public function renderScreen(array $flowDef, string $screenId, array $ctx = []): array
    {
        $screen = FlowEngine::getScreenById($flowDef, $screenId);
        if (! $screen) {
            throw new RuntimeException("Screen not found: {$screenId}");
        }

        $components = $screen['components'] ?? [];

        $messages = [];

        foreach ($components as $c) {
            $type = $c['type'] ?? '';

            switch ($type) {
                case 'TextBody':
                    // Text message
                    $text = (string) ($c['text'] ?? $c['body'] ?? '');
                    if ($text !== '') {
                        $messages[] = [
                            'type' => 'text',
                            'text' => $this->interpolate($text, $ctx),
                        ];
                    }
                    break;

                case 'Image':
                    // Image by URL (you can also support base64 if needed)
                    $url = (string) ($c['url'] ?? '');
                    if ($url !== '') {
                        $messages[] = [
                            'type' => 'image',
                            'url' => $url,
                            'caption' => (string) ($c['caption'] ?? ''),
                        ];
                    }
                    break;

                case 'TextInput':
                    // Prompt user for a text answer; we send caption + hint
                    $label = (string) ($c['label'] ?? 'Enter text');
                    $placeholder = (string) ($c['placeholder'] ?? '');
                    $messages[] = [
                        'type' => 'text',
                        'text' => trim($label.($placeholder ? "\n{$placeholder}" : '')),
                    ];
                    break;

                case 'Dropdown':
                    // Render as a WhatsApp "list" (title + options)
                    $label = (string) ($c['label'] ?? 'Choose');
                    $options = $c['options'] ?? [];
                    $list = [];
                    foreach ($options as $opt) {
                        $list[] = [
                            'id' => (string) ($opt['value'] ?? ''),
                            'title' => (string) ($opt['label'] ?? ($opt['value'] ?? '')),
                        ];
                    }
                    if ($list) {
                        $messages[] = [
                            'type' => 'list',
                            'title' => $label,
                            'buttonText' => (string) ($c['button_text'] ?? 'Select'),
                            'items' => $list,
                            // You can add 'section' support if needed
                        ];
                    } else {
                        // Fall back to text if no items
                        $messages[] = [
                            'type' => 'text',
                            'text' => $label,
                        ];
                    }
                    break;

                case 'DatePicker':
                    // WhatsApp has no native date picker; prompt a text pattern
                    $label = (string) ($c['label'] ?? 'Choose a date');
                    $fmt = (string) ($c['format'] ?? 'YYYY-MM-DD');
                    $messages[] = [
                        'type' => 'text',
                        'text' => "{$label}\nFormat: {$fmt}",
                    ];
                    break;

                default:
                    // Unknown component -> ignore or log
                    break;
            }
        }

        // Fallback if screen has no components: show its title
        if (! $messages && ! empty($screen['title'])) {
            $messages[] = [
                'type' => 'text',
                'text' => (string) $screen['title'],
            ];
        }

        return $messages;
    }

    protected function interpolate(string $text, array $ctx): string
    {
        // Very simple {{key}} replacement from ctx
        return preg_replace_callback('/\\{\\{\\s*([a-zA-Z0-9_\\.]+)\\s*\\}\\}/', function ($m) use ($ctx) {
            $key = $m[1];
            $val = data_get($ctx, $key);

            return $val === null ? '' : (string) $val;
        }, $text);
    }
}
