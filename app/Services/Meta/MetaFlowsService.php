<?php

namespace App\Services\Meta;

use App\Models\FlowVersion;
use App\Models\MetaFlow;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MetaFlowsService
{
    protected string $base;

    public function __construct()
    {
        $v = config('services.meta.graph_version', env('META_GRAPH_VERSION', 'v21.0'));
        $this->base = "https://graph.facebook.com/{$v}";
    }

    /**
     * Create a draft Flow on Meta, ensure endpoint/validation, and store mapping.
     */
    public function create(FlowVersion $fv): MetaFlow
    {
        $provider = $fv->provider;
        if (! $provider) {
            throw new RuntimeException('FlowVersion must be associated with a Provider to publish to Meta.');
        }

        $token = (string) $provider->api_token;
        $wabaId = (string) data_get($provider->meta, 'waba_id');

        if ($token === '' || $wabaId === '') {
            throw new RuntimeException('Provider is missing Meta credentials (WABA ID or API Token).');
        }

        // Resolve categories (ServiceType -> Provider meta -> config default) and normalize to Meta enum
        $rawCategories = $fv->serviceType?->categories
            ?: (array) data_get($provider->meta, 'categories', [])
            ?: (array) config('meta.flows.default_categories', ['OTHER']);

        $categories = $this->normalizeCategories($rawCategories);
        if (empty($categories)) {
            $allowed = implode(', ', $this->getAllowedCategories());
            throw new RuntimeException("ServiceType for the FlowVersion is missing valid 'categories'. Allowed: [{$allowed}].");
        }

        // Parse & map definition JSON
        $def = $fv->definition;
        if (! is_array($def)) {
            $def = json_decode((string) $def, true);
        }
        if (! is_array($def)) {
            throw new RuntimeException('FlowVersion.definition must be valid JSON.');
        }
        $flowJson = $this->mapToMetaJson($def);

        Log::info('MetaFlowsService:create request', [
            'waba_id' => $wabaId,
            'categories' => $categories,
            'flow_name' => $fv->name ?: "Flow #{$fv->id}",
        ]);

        // ---- CREATE DRAFT FLOW ----
        $res = Http::withToken($token)
            ->asForm()
            ->post("{$this->base}/{$wabaId}/flows", [
                'name' => $fv->name ?: "Flow #{$fv->id}",
                'categories' => $categories, // array -> categories[0]… via asForm()
                'flow_json' => json_encode($flowJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ])
            ->throw();

        $flowId = (string) data_get($res->json(), 'id');
        if ($flowId === '') {
            throw new RuntimeException('Meta did not return a flow id.');
        }

        // ---- ENDPOINT & ENCRYPTION (v3+ data-exchange flows) ----
        if ($this->flowNeedsEndpoint($flowJson)) {
            // Optional: ensure business encryption key is set (idempotent)
            if ($pubKey = (string) data_get($provider->meta, 'business_public_key')) {
                Http::withToken($token)
                    ->asForm()
                    ->post("{$this->base}/{$wabaId}/whatsapp_business_encryption", [
                        'business_encryption_public_key' => $pubKey,
                    ])->throw();
            }

            // Set endpoint_uri on the Flow (v3.0+ requires this via API)
            $endpointUri = (string) (data_get($provider->meta, 'flows.endpoint_uri')
                ?? config('meta.flows.default_endpoint_uri', ''));

            if ($endpointUri === '') {
                throw new RuntimeException(
                    'This Flow uses data exchange. Please set provider.meta.flows.endpoint_uri or meta.flows.default_endpoint_uri.'
                );
            }

            Http::withToken($token)
                ->asForm()
                ->post("{$this->base}/{$flowId}", [
                    'endpoint_uri' => $endpointUri,
                ])->throw();
        }

        // ---- VALIDATION PREFLIGHT ----
        $details = $this->getFlowDetails($token, $flowId);
        $errors = (array) data_get($details, 'validation_errors', []);
        if (! empty($errors)) {
            throw new RuntimeException('Flow has validation errors: '.json_encode($errors, JSON_UNESCAPED_UNICODE));
        }

        return MetaFlow::updateOrCreate(
            ['flow_version_id' => $fv->id],
            [
                'meta_flow_id' => $flowId,
                'status' => 'draft',
                'last_payload' => $details,
            ]
        );
    }

    /**
     * Publish a Flow (locks it for sending).
     */
    public function publish(MetaFlow $mf): MetaFlow
    {
        $provider = $mf->flowVersion->provider;
        if (! $provider) {
            throw new RuntimeException('MetaFlow must be associated with a Provider to publish.');
        }

        $token = (string) $provider->api_token;
        if ($token === '') {
            throw new RuntimeException('Provider is missing an API Token.');
        }

        // Final preflight validation
        $details = $this->getFlowDetails($token, $mf->meta_flow_id);
        $errors = (array) data_get($details, 'validation_errors', []);
        if (! empty($errors)) {
            throw new RuntimeException('Flow has validation errors: '.json_encode($errors, JSON_UNESCAPED_UNICODE));
        }

        Http::withToken($token)
            ->asForm()
            ->post("{$this->base}/{$mf->meta_flow_id}/publish", [])
            ->throw();

        $mf->status = 'published';
        $mf->published_at = now();
        $mf->last_payload = $details;
        $mf->save();

        return $mf;
    }

    /**
     * OPTIONAL: Set business encryption public key on the WABA (for endpoint/data flows).
     */
    public function setBusinessEncryptionKey(string $publicPem, string $wabaId, string $token): void
    {
        Http::withToken($token)
            ->asForm()
            ->post("{$this->base}/{$wabaId}/whatsapp_business_encryption", [
                'business_encryption_public_key' => $publicPem,
            ])->throw();
    }

    /**
     * Map your builder JSON → Meta Flow JSON (pass-through for now).
     */
    protected function mapToMetaJson(array $def): array
    {
        // If your builder already speaks Meta's schema, return as is.
        // Do translation here if your internal format differs.
        return $def;
    }

    /**
     * Decide if this Flow JSON uses data-exchange (thus needs endpoint_uri on v3+).
     */
    protected function flowNeedsEndpoint(array $flowJson): bool
    {
        $version = (string) data_get($flowJson, 'version', '');
        $screens = (array) data_get($flowJson, 'screens', []);
        $v3plus = $version !== '' && version_compare($version, '3.0', '>=');

        // If older spec (<3.0), assume endpoint not required (unless you detect data_channel_uri).
        if (! $v3plus) {
            return (bool) data_get($flowJson, 'data_channel_uri'); // legacy hint
        }

        // Heuristic: look for actions implying remote calls / data exchange
        foreach ($screens as $screen) {
            $actions = (array) data_get($screen, 'actions', []);
            foreach ($actions as $a) {
                $name = strtoupper((string) data_get($a, 'name', ''));
                $type = strtoupper((string) data_get($a, 'type', ''));
                if (
                    str_contains($name, 'DATA')
                    || str_contains($name, 'EXCHANGE')
                    || str_contains($name, 'FETCH')
                    || str_contains($name, 'SUBMIT')
                    || str_contains($type, 'REMOTE')
                    || str_contains($type, 'NETWORK')
                ) {
                    return true;
                }
            }
        }

        // Also consider explicit top-level hints
        if (data_get($flowJson, 'data_channel_uri')) {
            return true;
        }

        return false;
    }

    /**
     * Fetch flow details (status, validation errors, etc.) from Graph.
     */
    protected function getFlowDetails(string $token, string $flowId): array
    {
        $fields = implode(',', [
            'id',
            'name',
            'status',
            'categories',
            'validation_errors',
            'json_version',
            'data_api_version',
            'endpoint_uri',
        ]);

        $res = Http::withToken($token)
            ->get("{$this->base}/{$flowId}", ['fields' => $fields])
            ->throw();

        return (array) $res->json();
    }

    /**
     * Normalize internal categories to Meta's enum:
     *  - uppercases
     *  - applies alias map (config/meta.php)
     *  - validates against allowed list
     */
    protected function normalizeCategories(array|string|null $raw): array
    {
        $allowed = $this->getAllowedCategories();
        $aliases = (array) config('meta.flows.category_aliases', [
            // domain → Meta enum (extend as needed)
            'FOOD_DELIVERY' => 'OTHER',
            'RESTAURANT' => 'OTHER',
            'ORDERING' => 'OTHER',
            'REORDER' => 'OTHER',
            'CHECKOUT' => 'OTHER',
            'CONTACT' => 'CONTACT_US',
            'CONTACT_US' => 'CONTACT_US',
            'SUPPORT' => 'CUSTOMER_SUPPORT',
            'HELP' => 'CUSTOMER_SUPPORT',
            'RATING' => 'SURVEY',
            'FEEDBACK' => 'SURVEY',
            'APPOINTMENT' => 'APPOINTMENT_BOOKING',
            'BOOKING' => 'APPOINTMENT_BOOKING',
            'LOGIN' => 'SIGN_IN',
            'SIGNUP' => 'SIGN_UP',
            // template categories → best-fit flow categories
            'UTILITY' => 'OTHER',
            'MARKETING' => 'LEAD_GENERATION',
        ]);

        $arr = is_array($raw) ? $raw : (array) $raw;

        $normalized = collect($arr)
            ->filter(fn ($c) => filled($c))
            ->map(fn ($c) => strtoupper((string) $c))
            ->map(fn ($c) => $aliases[$c] ?? $c)
            ->unique()
            ->values()
            ->all();

        $invalid = array_values(array_diff($normalized, $allowed));
        if (! empty($invalid)) {
            $inv = implode(', ', $invalid);
            $all = implode(', ', $allowed);
            throw new RuntimeException("Invalid Flow categories: [{$inv}]. Allowed: [{$all}].");
        }

        return $normalized;
    }

    /**
     * Meta's allowed enum for Flow categories (override via config if desired).
     */
    protected function getAllowedCategories(): array
    {
        return (array) config('meta.flows.allowed_categories', [
            'SIGN_UP',
            'SIGN_IN',
            'APPOINTMENT_BOOKING',
            'LEAD_GENERATION',
            'CONTACT_US',
            'CUSTOMER_SUPPORT',
            'SURVEY',
            'OTHER',
        ]);
    }
}
