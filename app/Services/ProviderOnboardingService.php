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
    public function onboard(Provider $provider): ?Flow
    {
        $serviceType = $provider->serviceType;
        if (! $serviceType) {
            Log::warning('Onboarding aborted: Provider has no serviceType.', ['provider_id' => $provider->id]);

            return null;
        }

        $template = $serviceType->defaultFlowTemplate;
        if (! $template) {
            Log::warning('Onboarding aborted: No default FlowTemplate for serviceType.', [
                'provider_id' => $provider->id, 'service_type_id' => $serviceType->id,
            ]);

            return null;
        }

        $templateVersion = $template->latestVersion;
        if (! $templateVersion || $templateVersion->status !== 'published') {
            Log::warning('Onboarding aborted: Template has no published version linked.', [
                'provider_id' => $provider->id, 'template_id' => $template->id,
            ]);

            return null;
        }

        return DB::transaction(function () use ($provider, $template, $templateVersion) {
            $flow = Flow::firstOrCreate(
                [
                    'provider_id' => $provider->id,
                    'trigger_keyword' => $template->slug,
                ],
                [
                    'name' => $template->name ?? 'Provider Default Flow',
                    'is_active' => true,
                    'meta' => ['onboarded_from_template_id' => $template->id],
                ]
            );

            $versionToTrigger = null;

            if (! $flow->versions()->exists()) {
                $newVersion = $this->cloneTemplateVersion($templateVersion, $flow, $template);

                if (Schema::hasColumn('flows', 'live_version_id')) {
                    $flow->forceFill(['live_version_id' => $newVersion->id])->save();
                }
                Log::info('Onboarding successful: Cloned new version.', ['flow_id' => $flow->id, 'new_version_id' => $newVersion->id]);
                $versionToTrigger = $newVersion;
            } else {
                Log::info('Onboarding check: Flow already exists with versions.', ['flow_id' => $flow->id]);
                $versionToTrigger = $flow->liveVersion()->first() ?? $flow->versions()->latest('id')->first();
            }

            if ($versionToTrigger) {
                FlowTrigger::updateOrCreate(
                    [
                        'keyword' => $flow->trigger_keyword,
                        'provider_id' => $provider->id,
                    ],
                    [
                        'service_type_id' => $provider->service_type_id,
                        'flow_version_id' => $versionToTrigger->id,
                        'use_latest_published' => true,
                        'is_active' => true,
                    ]
                );
                Log::info('Onboarding: Ensured FlowTrigger exists and is up-to-date.', ['flow_id' => $flow->id, 'trigger_keyword' => $flow->trigger_keyword]);
            } else {
                Log::warning('Onboarding: Could not find a version for the flow, so no trigger was created.', ['flow_id' => $flow->id]);
            }

            return $flow->fresh();
        });
    }

    protected function cloneTemplateVersion(FlowVersion $templateVersion, Flow $flow, FlowTemplate $template): FlowVersion
    {
        // Calculate the next available version number for this template to satisfy unique constraints.
        $nextVersionNumber = (int) (FlowVersion::where('flow_template_id', $template->id)->max('version') ?? 0) + 1;

        $newVersionData = $templateVersion->replicate([
            'id', 'flow_id', 'is_template', 'version',
        ])->toArray();

        return FlowVersion::create(array_merge($newVersionData, [
            'flow_id' => $flow->id,
            'flow_template_id' => $template->id,
            'provider_id' => $flow->provider_id,
            'is_template' => false,
            'version' => $nextVersionNumber,
            'name' => "v{$nextVersionNumber}",
            'published_at' => now(),
        ]));
    }
}
