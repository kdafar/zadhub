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

    protected function token(): string
    {
        return env('META_ACCESS_TOKEN');
    }

    protected function waba(): string
    {
        return env('META_WABA_ID');
    }

    /** Create a draft Flow on Meta and store mapping */
    public function create(FlowVersion $fv): MetaFlow
    {
        // Convert your internal JSON to Meta Flow JSON (keep 1:1 if already compatible)
        $flowJson = $this->mapToMetaJson($fv->definition ?? []);

        $res = Http::withToken($this->token())
            ->post("{$this->base}/{$this->waba()}/flows", [
                // The exact keys must follow Meta docs; common fields:
                // name: human label in WhatsApp Manager
                // flow_json: JSON string of your flow schema
                'name' => $fv->name ?: "Flow #{$fv->id}",
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
        $res = Http::withToken($this->token())
            ->post("{$this->base}/{$mf->meta_flow_id}/publish", []);
        $res->throw();

        $mf->status = 'published';
        $mf->published_at = now();
        $mf->last_payload = $res->json();
        $mf->save();

        return $mf;
    }

    /** OPTIONAL: set business encryption public key (for endpoint flows) */
    public function setBusinessEncryptionKey(string $publicPem): void
    {
        Http::withToken($this->token())
            ->post("{$this->base}/{$this->waba()}/whatsapp_business_encryption", [
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
