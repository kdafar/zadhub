<?php

namespace App\Services\Meta;

use App\Models\Provider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MetaMessageTemplateService
{
    protected string $base;

    public function __construct()
    {
        $v = config('services.meta.graph_version', env('META_GRAPH_VERSION', 'v21.0'));
        $this->base = "https://graph.facebook.com/{$v}";
    }

    public function create(array $templateData, Provider $provider): array
    {
        $token = (string) $provider->api_token;
        $wabaId = (string) data_get($provider->meta, 'waba_id');

        if (empty($token) || empty($wabaId)) {
            throw new RuntimeException('Provider is missing Meta credentials (WABA ID or API Token).');
        }

        $payload = [
            'name' => $templateData['name'],
            'language' => $templateData['language'],
            'category' => $templateData['category'],
            'components' => $this->formatComponents($templateData['components'] ?? []),
        ];

        Log::info('MetaMessageTemplateService:create request', [
            'waba_id' => $wabaId,
            'payload' => $payload,
        ]);

        $response = Http::withToken($token)
            ->post("{$this->base}/{$wabaId}/message_templates", $payload)
            ->throw();

        return $response->json();
    }

    protected function formatComponents(array $components): array
    {
        return array_map(function ($component) {
            $formatted = ['type' => $component['type']];

            if (!empty($component['text'])) {
                $formatted['text'] = $component['text'];
            }

            if (!empty($component['format'])) {
                $formatted['format'] = $component['format'];
            }

            if (!empty($component['buttons'])) {
                $formatted['buttons'] = array_map(function ($button) {
                    $formattedButton = [
                        'type' => $button['type'],
                        'text' => $button['text'],
                    ];
                    if (!empty($button['url'])) {
                        $formattedButton['url'] = $button['url'];
                    }
                    return $formattedButton;
                }, $component['buttons']);
            }

            return $formatted;
        }, $components);
    }
}
