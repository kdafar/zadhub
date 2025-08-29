<?php

namespace App\Services;

use App\Models\Flow;
use App\Models\FlowTemplate;
use App\Models\FlowTrigger;
use App\Models\FlowVersion;
use App\Models\Provider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ProviderOnboardingService
{
    public function onboard(Provider $provider): void
    {
        $serviceType = $provider->serviceType;
        if (! $serviceType) {
            Log::warning('Onboarding aborted: Provider has no serviceType.', ['provider_id' => $provider->id]);
            return;
        }

        $templates = $serviceType->flowTemplates()->with('latestVersion')->get();

        if ($templates->isEmpty()) {
            Log::info('Onboarding: No flow templates to clone for service type.', [
                'provider_id' => $provider->id,
                'service_type_id' => $serviceType->id,
            ]);
            return;
        }

        DB::transaction(function () use ($provider, $templates) {
            foreach ($templates as $template) {
                $templateVersion = $template->latestVersion;
                if (!$templateVersion) continue;

                // Create a new Flow for the provider, based on the template
                $flow = Flow::firstOrCreate(
                    [
                        'provider_id' => $provider->id,
                        'trigger_keyword' => $template->slug, // Use template slug as the default trigger
                    ],
                    [
                        'name' => $template->name,
                        'is_active' => true,
                        'meta' => ['cloned_from_template_id' => $template->id],
                    ]
                );

                // Only clone if the provider doesn't already have a version for this flow
                if ($flow->versions()->doesntExist()) {
                    $newVersionData = $templateVersion->replicate([
                        'id', 'flow_id', 'is_template', 'version',
                    ])->toArray();

                    $newVersion = $flow->versions()->create(array_merge($newVersionData, [
                        'provider_id' => $provider->id,
                        'is_template' => false,
                        'version' => 1, // Start provider's version at 1
                        'name' => 'v1',
                        'status' => 'published',
                        'published_at' => now(),
                    ]));

                    Log::info('Onboarding: Cloned new version for provider.', [
                        'flow_id' => $flow->id,
                        'new_version_id' => $newVersion->id
                    ]);
                }

                // Ensure a trigger exists for this flow
                FlowTrigger::updateOrCreate(
                    [
                        'keyword' => $flow->trigger_keyword,
                        'provider_id' => $provider->id,
                    ],
                    [
                        'service_type_id' => $provider->service_type_id,
                        'use_latest_published' => true, // Always use the latest published version for this provider's flow
                        'is_active' => true,
                    ]
                );
            }
        });
    }
}
