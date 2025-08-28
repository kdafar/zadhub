<?php

namespace App\Services\Meta;

use App\Models\FlowVersion;
use App\Models\MetaFlow;
use Illuminate\Support\Facades\Http;

class MetaFlowsService
{
    protected string $base;

    public function __construct()
    {
        $v = config('services.meta.graph_version', env('META_GRAPH_VERSION', 'v21.0'));
        $this->base = "https://graph.facebook.com/{$v}";
    }

    /** Create a draft Flow on Meta and store mapping */
    public function create(FlowVersion $fv): MetaFlow
    {
        $provider = $fv->provider;
        if (! $provider) {
            throw new \Exception('FlowVersion must be associated with a Provider to publish to Meta.');
        }

        $token = $provider->api_token;
        $wabaId = data_get($provider->meta, 'waba_id');

        if (! $token || ! $wabaId) {
            throw new \Exception("Provider is missing Meta credentials (WABA ID or API Token).");
        }

        $categories = $fv->serviceType?->categories;
        if (empty($categories)) {
            throw new \Exception("ServiceType for the FlowVersion is missing the 'categories' attribute.");
        }

        // Convert your internal JSON to Meta Flow JSON (keep 1:1 if already compatible)
        $def = $fv->definition;
        if (! is_array($def)) {
            $def = json_decode((string) $def, true) ?? [];
        }
        $flowJson = $this->mapToMetaJson($def);

        $res = Http::withToken($token)
            ->post("{$this->base}/{$wabaId}/flows", [
                'name' => $fv->name ?: "Flow #{$fv->id}",
                'categories' => $categories,
                'flow_json' => json_encode($flowJson, JSON_UNESCAPED_UNICODE),
            ]);

        $res->throw();
        $metaId = data_get($res->json(), 'id'); // Meta returns created object id

        return MetaFlow::updateOrCreate(
            ['flow_version_id' => $fv->id],
            ['meta_flow_id' => $metaId, 'status' => 'draft', 'last_payload' => $res->json()]
        );
    }

    /** Publish a Flow (locks it for sending) */
    public function publish(MetaFlow $mf): MetaFlow
    {
        $provider = $mf->flowVersion->provider;
        if (! $provider) {
            throw new \Exception('MetaFlow must be associated with a Provider to publish.');
        }

        $token = $provider->api_token;
        if (! $token) {
            throw new \Exception('Provider is missing an API Token.');
        }

        $res = Http::withToken($token)
            ->post("{$this->base}/{$mf->meta_flow_id}/publish", []);
        $res->throw();

        $mf->status = 'published';
        $mf->published_at = now();
        $mf->last_payload = $res->json();
        $mf->save();

        return $mf;
    }

    /** OPTIONAL: set business encryption public key (for endpoint flows) */
    public function setBusinessEncryptionKey(string $publicPem, string $wabaId, string $token): void
    {
        Http::withToken($token)
            ->post("{$this->base}/{$wabaId}/whatsapp_business_encryption", [
                'business_encryption_public_key' => $publicPem,
            ])->throw();
    }

    /** Map your builder JSON → Meta Flow JSON (keep minimal for now) */
    protected function mapToMetaJson(array $def): array
    {
        // If your JSON already matches Meta's schema, just return $def.
        // Otherwise translate components here.
        return $def;
    }
}
