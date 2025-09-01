<?php

namespace App\Services\Meta;

use App\Models\Provider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaFetchService
{
    protected string $base;

    public function __construct()
    {
        $v = config('services.meta.graph_version', env('META_GRAPH_VERSION', 'v21.0'));
        $this->base = "https://graph.facebook.com/{$v}";
    }

    public function fetchMessageTemplates(Provider $provider): array
    {
        $token = (string) $provider->api_token;
        $wabaId = (string) data_get($provider->meta, 'waba_id');

        if (empty($token) || empty($wabaId)) {
            throw new RuntimeException('Provider is missing Meta credentials (WABA ID or API Token).');
        }

        $response = Http::withToken($token)
            ->get("{$this->base}/{$wabaId}/message_templates", [
                'fields' => 'name,status,category,language,components',
                'limit' => 100, // Adjust as needed
            ])
            ->throw();

        return $response->json('data', []);
    }

    public function fetchFlows(Provider $provider): array
    {
        $token = (string) $provider->api_token;
        $wabaId = (string) data_get($provider->meta, 'waba_id');

        if (empty($token) || empty($wabaId)) {
            throw new RuntimeException('Provider is missing Meta credentials (WABA ID or API Token).');
        }

        $response = Http::withToken($token)
            ->get("{$this->base}/{$wabaId}/flows", [
                'fields' => 'name,status,json_version,id',
                'limit' => 100,
            ])
            ->throw();

        return $response->json('data', []);
    }

    public function fetchFlowDefinition(string $flowId, Provider $provider): array
    {
        $token = (string) $provider->api_token;

        if (empty($token)) {
            throw new RuntimeException('Provider is missing an API Token.');
        }

        $response = Http::withToken($token)
            ->get("{$this->base}/{$flowId}", [
                'fields' => 'definition',
            ])
            ->throw();

        return $response->json('definition', []);
    }
}
