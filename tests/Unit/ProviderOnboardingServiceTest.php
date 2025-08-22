<?php

namespace Tests\Unit;

use App\Models\Flow;
use App\Models\FlowTemplate;
use App\Models\FlowVersion;
use App\Models\Provider;
use App\Models\ServiceType;
use App\Services\ProviderOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderOnboardingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_onboard_creates_flow_from_template_and_links_version(): void
    {
        // 1) Arrange
        $serviceType = ServiceType::factory()->create();
        $flowTemplate = FlowTemplate::factory()->create([
            'service_type_id' => $serviceType->id,
        ]);
        $templateVersion = FlowVersion::factory()->create([
            'flow_template_id' => $flowTemplate->id,
        ]);

        $serviceType->update(['default_flow_template_id' => $flowTemplate->id]);
        $flowTemplate->update(['latest_version_id' => $templateVersion->id]);

        $provider = Provider::factory()->create([
            'service_type_id' => $serviceType->id,
        ]);

        // 2) Act
        (new ProviderOnboardingService)->onboard($provider);

        // 3) Assert: flow created with expected attributes
        $this->assertDatabaseHas('flows', [
            'provider_id' => $provider->id,
            'name' => $flowTemplate->name,
            'trigger_keyword' => $flowTemplate->slug,
        ]);

        $flow = Flow::where('provider_id', $provider->id)->firstOrFail();

        // Exactly one provider-specific version created and linked
        $this->assertSame(1, $flow->versions()->count(), 'Expected exactly one copied flow version.');
        $copiedVersion = $flow->versions()->first();

        $this->assertNotNull($copiedVersion);
        $this->assertNotSame($templateVersion->id, $copiedVersion->id, 'Copied version must be a new row.');
        $this->assertSame($flow->id, $copiedVersion->flow_id, 'Copied version must reference the new flow.');

        // Linked to template (sqlite schema keeps NOT NULL on flow_template_id)
        $this->assertDatabaseHas('flow_versions', [
            'id' => $copiedVersion->id,
            'flow_id' => $flow->id,
            'flow_template_id' => $flowTemplate->id,
        ]);

        // Optional if you bumped the version in the service:
        $this->assertGreaterThanOrEqual($templateVersion->version + 1, $copiedVersion->version);

        // Idempotency
        (new ProviderOnboardingService)->onboard($provider);
        $this->assertSame(1, Flow::where('provider_id', $provider->id)->count(), 'Onboard should not create duplicate flows.');
        $this->assertSame(1, $flow->versions()->count(), 'Onboard should not create duplicate versions.');

    }
}
