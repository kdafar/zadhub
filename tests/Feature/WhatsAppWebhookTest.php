<?php

namespace Tests\Feature;

use App\Models\Flow;
use App\Models\FlowTrigger;
use App\Models\FlowVersion;
use App\Models\MetaFlow;
use App\Models\Provider;
use App\Services\WhatsAppApiServiceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Keep a global fallback for tests that might not create a provider meta
        Config::set('services.whatsapp.app_secret', 'global-test-secret');
        Config::set('services.whatsapp.verify_token', 'global-verify-token');
        Config::set('services.whatsapp.fake', true);

        $this->app->singleton(WhatsAppApiServiceFactory::class, function () {
            return new class extends WhatsAppApiServiceFactory
            {
                public function make(?\App\Models\Provider $provider = null): \App\Services\WhatsAppApiService|\App\Services\WhatsAppApiServiceFake
                {
                    return new \App\Services\WhatsAppApiServiceFake;
                }
            };
        });
    }

    public function test_it_verifies_a_webhook_using_provider_specific_token()
    {
        // 1. Arrange
        $provider = Provider::factory()->create([
            'meta' => [
                'verify_token' => 'provider-specific-token',
            ],
        ]);

        $challenge = 'challenge-string';

        // 2. Act
        $response = $this->getJson("/api/whatsapp/webhook/{$provider->slug}?hub.mode=subscribe&hub.verify_token=provider-specific-token&hub.challenge={$challenge}");

        // 3. Assert
        $response->assertStatus(200);
        $response->assertSee($challenge);
    }

    public function test_it_handles_a_valid_incoming_webhook_and_starts_a_flow()
    {
        // 1. Arrange
        $provider = Provider::factory()->create([
            'meta' => [
                'app_secret' => 'provider-specific-secret',
            ],
        ]);
        $flow = Flow::factory()->create([
            'provider_id' => $provider->id,
            'trigger_keyword' => 'start',
        ]);
        $flowVersion = FlowVersion::factory()->create([
            'flow_id' => $flow->id,
            'provider_id' => $provider->id,
            'status' => 'published',
            'definition' => [
                'start_screen' => 'WELCOME',
                'screens' => [
                    'WELCOME' => ['id' => 'WELCOME', 'title' => 'Welcome Screen'],
                ],
            ],
        ]);
        MetaFlow::factory()->create([
            'flow_version_id' => $flowVersion->id,
            'meta_flow_id' => 'meta-flow-123',
        ]);
        FlowTrigger::factory()->create([
            'keyword' => 'start',
            'flow_version_id' => $flowVersion->id,
            'provider_id' => $provider->id,
        ]);

        $payload = $this->createWebhookPayload('12345', '98765', 'start');
        $signature = hash_hmac('sha256', json_encode($payload), 'provider-specific-secret');

        // 2. Act
        $response = $this->postJson("/api/whatsapp/webhook/{$provider->slug}", $payload, [
            'X-Hub-Signature-256' => 'sha256='.$signature,
        ]);

        // 3. Assert
        $response->assertStatus(204); // No Content is the new success response

        $this->assertDatabaseHas('whatsapp_sessions', [
            'phone' => '98765',
            'provider_id' => $provider->id,
            'flow_version_id' => $flowVersion->id,
            'current_screen' => 'WELCOME',
        ]);
    }

    public function test_it_rejects_invalid_signatures()
    {
        $provider = Provider::factory()->create();
        $response = $this->postJson("/api/whatsapp/webhook/{$provider->slug}", ['foo' => 'bar'], [
            'X-Hub-Signature-256' => 'sha256=invalid_signature',
        ]);

        $response->assertStatus(401);
    }

    protected function createWebhookPayload(string $phoneNumberId, string $from, string $text): array
    {
        return [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'metadata' => [
                                    'phone_number_id' => $phoneNumberId,
                                ],
                                'messages' => [
                                    [
                                        'from' => $from,
                                        'text' => [
                                            'body' => $text,
                                        ],
                                        'type' => 'text',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
