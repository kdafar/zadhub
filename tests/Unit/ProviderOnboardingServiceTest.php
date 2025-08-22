<?php

namespace Tests\Unit;

use App\Models\Flow;
use App\Models\FlowTemplate;
use App\Models\FlowVersion;
use App\Models\Provider;
use App\Models\ServiceType;
use App\Services\ProviderOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProviderOnboardingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_onboard_clones_published_template_version_and_links_everything_correctly(): void
    {
        // 1) Arrange a ServiceType + Template + Published Template Version
        $serviceType = ServiceType::factory()->create([
            'name' => 'Restaurants',
            'slug' => 'restaurants',
        ]);

        $flowTemplate = FlowTemplate::factory()->create([
            'service_type_id' => $serviceType->id,
            'name' => 'Base Restaurants Template',
            'slug' => 'restaurants_base',
        ]);

        $templateVersion = FlowVersion::factory()->create([
            'flow_template_id' => $flowTemplate->id,
            'service_type_id' => $serviceType->id,
            'is_template' => true,
            'status' => 'published',
            'published_at' => Carbon::now()->subMinute(),
            'definition' => json_encode([
                'start_screen' => 'WELCOME',
                'screens' => [
                    'WELCOME' => [
                        'type' => 'text',
                        'message' => 'Hi {{name}}! Welcome to Restaurants.',
                        'next' => null,
                    ],
                ],
            ]),
            'version' => 1,
            'name' => 'v1',
        ]);

        $flowTemplate->update(['latest_version_id' => $templateVersion->id]);

        $provider = Provider::factory()->create([
            'service_type_id' => $serviceType->id,
            'name' => 'Demo Provider',
        ]);

        // 2) Act
        $flow = (new ProviderOnboardingService)->onboard($provider);

        // 3) Assert — Flow was created and is active
        $this->assertInstanceOf(Flow::class, $flow);
        $this->assertDatabaseHas('flows', [
            'id' => $flow->id,
            'provider_id' => $provider->id,
            'is_active' => 1,
        ]);

        // Name/keyword come from the template (as implemented by the service)
        $this->assertSame($flowTemplate->name, $flow->name);
        $this->assertSame($flowTemplate->slug, $flow->trigger_keyword);

        // Exactly one provider-specific version created and linked to this flow
        $this->assertSame(1, $flow->versions()->count(), 'Expected exactly one copied flow version.');
        $copiedVersion = $flow->versions()->first();
        $this->assertNotNull($copiedVersion);
        $this->assertNotSame($templateVersion->id, $copiedVersion->id, 'Copied version must be a new row.');
        $this->assertSame($flow->id, $copiedVersion->flow_id, 'Copied version must reference the new flow.');

        // Copied version must be non-template, published, and linked to provider/serviceType/template
        $this->assertDatabaseHas('flow_versions', [
            'id' => $copiedVersion->id,
            'flow_id' => $flow->id,
            'flow_template_id' => $flowTemplate->id,
            'provider_id' => $provider->id,
            'service_type_id' => $serviceType->id,
            'is_template' => 0,
            'status' => 'published',
        ]);
        $this->assertNotNull($copiedVersion->published_at, 'Copied version should be published now.');

        // Definition should be copied verbatim
        $this->assertSame($templateVersion->definition, $copiedVersion->definition);

        // Versioning: cloned version may inherit the same number; assert >= template version (allows bumping if you choose)
        $this->assertGreaterThanOrEqual($templateVersion->version, $copiedVersion->version);

        // If flows has live_version_id, it should point to the copied version
        if (Schema::hasColumn('flows', 'live_version_id')) {
            $this->assertSame($copiedVersion->id, $flow->fresh()->live_version_id);
        }

        // 4) Idempotency — rerunning should not create duplicates
        (new ProviderOnboardingService)->onboard($provider);

        $this->assertSame(
            1,
            Flow::where('provider_id', $provider->id)->count(),
            'Onboard should not create duplicate flows.'
        );
        $this->assertSame(
            1,
            $flow->fresh()->versions()->count(),
            'Onboard should not create duplicate versions.'
        );
    }
}
