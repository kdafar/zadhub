<?php

namespace App\Services;

use App\Models\Flow;
use App\Models\Provider;
use App\Models\ServiceType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProviderOnboardingService
{
    public function onboard(Provider $provider): void
    {
        if (! $provider->service_type_id) {
            Log::info("Provider {$provider->id} has no service type. Skipping onboarding.");
            return;
        }

        $serviceType = ServiceType::find($provider->service_type_id);
        if (! $serviceType) {
            Log::warning("ServiceType not found for provider {$provider->id}.");
            return;
        }

        // belongsTo relation; no need for ->first()
        $flowTemplate = $serviceType->defaultFlowTemplate;
        if (! $flowTemplate) {
            Log::info("ServiceType {$serviceType->id} has no default flow template. Skipping flow creation.");
            return;
        }

        // Avoid duplicates
        $existingFlow = Flow::where('provider_id', $provider->id)
            ->where('name', $flowTemplate->name)
            ->first();

        if ($existingFlow) {
            Log::info("Provider {$provider->id} already has a flow based on template {$flowTemplate->id}.");
            return;
        }

        DB::transaction(function () use ($provider, $flowTemplate) {
    $flow = Flow::create([
        'provider_id'     => $provider->id,
        'name'            => $flowTemplate->name,
        'trigger_keyword' => $flowTemplate->slug,
        'is_active'       => true,
        'meta'            => ['source_template_id' => $flowTemplate->id],
    ]);

    // Prefer explicit latest_version_id; fallback to newest row
    $latestVersion = $flowTemplate->latestVersion
        ?? $flowTemplate->versions()->orderByDesc('id')->first();

    if (! $latestVersion) {
        Log::info("FlowTemplate {$flowTemplate->id} has no versions to copy.");
        return;
    }

    // Compute next version to avoid UNIQUE(flow_template_id, version)
    $nextVersion = (int) ($flowTemplate->versions()->max('version') ?? 0) + 1;

    $newVersion = $latestVersion->replicate();

    // Keep linkage (sqlite says NOT NULL), but avoid unique collision by bumping version
    $newVersion->flow_template_id = $flowTemplate->id;
    $newVersion->version          = $nextVersion;

    // Make it clearly a provider copy (only if columns exist)
    if (\Schema::hasColumn('flow_versions', 'is_template')) {
        $newVersion->is_template = false;
    }
    if (\Schema::hasColumn('flow_versions', 'use_latest_published')) {
        $newVersion->use_latest_published = false;
    }
    if (\Schema::hasColumn('flow_versions', 'meta')) {
        $meta = (array) ($newVersion->meta ?? []);
        $meta['source_template_id'] = $flowTemplate->id;
        $newVersion->meta = $meta;
    }

    // Link to the provider flow (sets flow_id)
    $newVersion->flow()->associate($flow);

    $newVersion->saveOrFail();

    Log::info("Onboarded provider {$provider->id} with flow {$flow->id} and version {$newVersion->version} from template {$flowTemplate->id}.");
});


    }
}
