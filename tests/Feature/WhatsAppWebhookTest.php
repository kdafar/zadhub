<?php

namespace Tests\Feature;

use App\Models\Flow;
use App\Models\FlowVersion;
use App\Models\MetaFlow;
use App\Models\Provider;
use App\Models\ServiceType;
use App\Services\WhatsAppApiServiceFactory;
use App\Services\WhatsAppApiServiceFake;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.whatsapp.app_secret', 'test-secret');
        Config::set('services.whatsapp.fake', true);

        // Mock the factory to ensure it returns the fake service
        $this->app->singleton(WhatsAppApiServiceFactory::class, function () {
            return new class extends WhatsAppApiServiceFactory {
                public function make(\App\Models\Provider $provider = null): \App\Services\WhatsAppApiService|\App\Services\WhatsAppApiServiceFake
                {
                    // We return a new fake instance that can be spied on or mocked
                    return new \App\Services\WhatsAppApiServiceFake();
                }
            };
        });
    }

    public function test_it_handles_a_valid_incoming_webhook_and_starts_a_flow()
    {
        // 1. Arrange
        $serviceType = ServiceType::factory()->create(['code' => 'start']);
        $provider = Provider::factory()->create(['service_type_id' => $serviceType->id]);
        $flow = Flow::factory()->create([
            'provider_id' => $provider->id,
            'trigger_keyword' => 'start',
        ]);
        $flowVersion = FlowVersion::factory()->create([
            'flow_id' => $flow->id,
            'status' => 'published',
            'definition' => [
                'screens' => [
                    ['id' => 'WELCOME', 'title' => 'Welcome Screen'],
                ],
            ],
        ]);
        MetaFlow::factory()->create([
            'flow_version_id' => $flowVersion->id,
            'meta_flow_id' => 'meta-flow-123',
        ]);

        $payload = $this->createWebhookPayload('12345', '98765', 'start');

        // 2. Act
        $response = $this->postJson('/api/whatsapp/webhook', $payload, [
            'X-Hub-Signature-256' => 'sha256=' . hash_hmac('sha256', json_encode($payload), config('services.whatsapp.app_secret')),
        ]);

        // 3. Assert
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('whatsapp_sessions', [
            'phone' => '98765',
            'provider_id' => $provider->id,
            'flow_version_id' => $flowVersion->id,
            'current_screen' => 'WELCOME',
        ]);
    }

    public function test_it_rejects_invalid_signatures()
    {
        $response = $this->postJson('/api/whatsapp/webhook', ['foo' => 'bar'], [
            'X-Hub-Signature-256' => 'sha256=invalid_signature',
        ]);

        $response->assertStatus(403);
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
