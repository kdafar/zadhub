<?php

namespace Tests\Unit;

use App\Models\Flow;
use App\Models\FlowVersion;
use App\Models\MetaFlow;
use App\Models\Provider;
use App\Models\ServiceType;
use App\Models\WhatsappSession;
use App\Services\FlowRenderer;
use App\Services\WhatsAppApiService;
use App\Services\WhatsAppApiServiceFactory;
use App\Services\WhatsAppMessageHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WhatsAppMessageHandlerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // The single WABA provider that sends all messages
        Provider::factory()->create();
    }

    public function test_it_starts_a_flow_when_a_trigger_keyword_is_received()
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
            'definition' => [ // <-- FIX: Changed from 'definition'
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

        // Mock dependencies
        $apiServiceMock = Mockery::mock(WhatsAppApiService::class);
        $apiServiceFactoryMock = Mockery::mock(WhatsAppApiServiceFactory::class);
        $flowRendererMock = Mockery::mock(FlowRenderer::class);

        $apiServiceFactoryMock->shouldReceive('make')->withAnyArgs()->andReturn($apiServiceMock);
        $flowRendererMock->shouldReceive('renderScreen')->andReturn(['id' => 'WELCOME', 'title' => 'Welcome!']);
        $apiServiceMock->shouldReceive('sendFlowMessage')->once();

        // 2. Act
        $handler = new WhatsAppMessageHandler($apiServiceFactoryMock, $flowRendererMock);
        $handler->process($payload);

        // 3. Assert
        $this->assertDatabaseHas('whatsapp_sessions', [
            'phone' => '98765',
            'provider_id' => $provider->id,
            'flow_version_id' => $flowVersion->id,
            'current_screen' => 'WELCOME',
            'status' => 'active',
        ]);
    }

    public function test_it_handles_a_flow_reply_and_transitions_to_the_next_screen()
    {
        // 1. Arrange
        $provider = Provider::factory()->create();
        $flow = Flow::factory()->create(['provider_id' => $provider->id]);
        $flowVersion = FlowVersion::factory()->create([
            'flow_id' => $flow->id,
            'status' => 'published',
            'definition' => [
                'screens' => [
                    [
                        'id' => 'ASK_NAME',
                        'title' => 'Enter Name',
                        'children' => [
                            ['type' => 'text_input', 'data' => ['name' => 'user_name', 'is_required' => true]],
                        ],
                        'data' => ['next_screen_id' => 'THANK_YOU'],
                    ],
                    [
                        'id' => 'THANK_YOU',
                        'title' => 'Thank You',
                    ],
                ],
            ],
        ]);
        MetaFlow::factory()->create([
            'flow_version_id' => $flowVersion->id,
            'meta_flow_id' => 'meta-flow-123',
        ]);

        $session = WhatsappSession::factory()->create([
            'provider_id' => $provider->id,
            'flow_version_id' => $flowVersion->id,
            'current_screen' => 'ASK_NAME',
            'status' => 'active',
            'context' => [],
        ]);

        $payload = $this->createFlowReplyWebhookPayload('12345', $session->phone, ['user_name' => 'John Doe']);

        // Mock dependencies
        $apiServiceMock = Mockery::mock(WhatsAppApiService::class);
        $apiServiceFactoryMock = Mockery::mock(WhatsAppApiServiceFactory::class);
        $flowRendererMock = Mockery::mock(FlowRenderer::class);

        $apiServiceFactoryMock->shouldReceive('make')->withAnyArgs()->andReturn($apiServiceMock);
        $flowRendererMock->shouldReceive('renderScreen')->andReturn([]);
        $apiServiceMock->shouldReceive('sendFlowMessage')->once();

        // 2. Act
        $handler = new WhatsAppMessageHandler($apiServiceFactoryMock, $flowRendererMock);
        $handler->process($payload);

        // 3. Assert
        $session->refresh();
        $this->assertEquals('THANK_YOU', $session->current_screen);
        $this->assertEquals('John Doe', $session->context['user_name']);
    }

    public function test_it_guides_a_user_through_service_and_provider_selection()
    {
        // 1. Arrange
        $serviceType = ServiceType::factory()->create(['code' => 'food']);
        // Naming ensures predictable order for selection ('1' or '2')
        Provider::factory()->create(['service_type_id' => $serviceType->id, 'name' => 'A Pizza Place']);
        $provider2 = Provider::factory()->create(['service_type_id' => $serviceType->id, 'name' => 'B Burger Joint']);
        $flow = Flow::factory()->create(['provider_id' => $provider2->id]);
        $flowVersion = FlowVersion::factory()->create([
            'flow_id' => $flow->id,
            'status' => 'published',
            'definition' => [ // <-- FIX: Add screen data for startFlow() to succeed
                'screens' => [
                    ['id' => 'START_SCREEN'],
                ],
            ],
        ]);
        MetaFlow::factory()->create(['flow_version_id' => $flowVersion->id, 'meta_flow_id' => 'meta-flow-456']);

        $apiServiceMock = Mockery::mock(WhatsAppApiService::class);
        $apiServiceFactoryMock = Mockery::mock(WhatsAppApiServiceFactory::class);
        $flowRendererMock = Mockery::mock(FlowRenderer::class);

        $apiServiceFactoryMock->shouldReceive('make')->withAnyArgs()->andReturn($apiServiceMock);
        $handler = new WhatsAppMessageHandler($apiServiceFactoryMock, $flowRendererMock);

        // --- PART 1: User sends service keyword ---

        // 2. Act (Part 1)
        $payload1 = $this->createWebhookPayload('111', 'user-phone', 'food');
        $apiServiceMock->shouldReceive('sendTextMessage')->once(); // Expects a list of providers
        $handler->process($payload1);

        // 3. Assert (Part 1)
        $session = WhatsappSession::where('phone', 'user-phone')->firstOrFail();
        $this->assertEquals('selecting_provider', $session->status); // This now passes

        // MODIFICATION: The test must also expect the 'state' key in the context array.
        $this->assertEquals([
            'state' => 'selecting_provider',
            'service_type_id' => $serviceType->id,
        ], $session->context);

        // --- PART 2: User sends provider choice ---

        // 4. Act (Part 2)
        $payload2 = $this->createWebhookPayload('111', 'user-phone', '2'); // Chooses 'B Burger Joint'
        $flowRendererMock->shouldReceive('renderScreen')->once()->andReturn([]);
        $apiServiceMock->shouldReceive('sendFlowMessage')->once();
        $handler->process($payload2);

        // 5. Assert (Part 2)
        $this->assertDatabaseHas('whatsapp_sessions', [
            'phone' => 'user-phone',
            'provider_id' => $provider2->id,
            'flow_version_id' => $flowVersion->id,
            'status' => 'active',
            'context' => '[]', // Context is cleared after selection
        ]);
    }

    public function test_it_uses_provider_specific_credentials()
    {
        // 1. Arrange
        $serviceType = ServiceType::factory()->create(['code' => 'start']);
        $provider = Provider::factory()->create([
            'service_type_id' => $serviceType->id,
            'whatsapp_phone_number_id' => '123456789',
            'api_token' => 'test-token',
        ]);
        $flow = Flow::factory()->create([
            'provider_id' => $provider->id,
            'trigger_keyword' => 'start',
        ]);
        $flowVersion = FlowVersion::factory()->create([
            'flow_id' => $flow->id,
            'status' => 'published',
            'definition' => ['screens' => [['id' => 'WELCOME']]],
        ]);
        MetaFlow::factory()->create([
            'flow_version_id' => $flowVersion->id,
            'meta_flow_id' => 'meta-flow-123',
        ]);

        $payload = $this->createWebhookPayload('12345', '98765', 'start');

        // Mock dependencies
        $apiServiceMock = Mockery::mock(WhatsAppApiService::class);
        $apiServiceFactoryMock = Mockery::mock(WhatsAppApiServiceFactory::class);
        $flowRendererMock = Mockery::mock(FlowRenderer::class);

        // Assert that the factory is called with the correct provider
        $apiServiceFactoryMock->shouldReceive('make')
            ->with(Mockery::on(function ($arg) use ($provider) {
                return $arg->id === $provider->id;
            }))
            ->once()
            ->andReturn($apiServiceMock);

        $flowRendererMock->shouldReceive('renderScreen')->andReturn(['id' => 'WELCOME', 'title' => 'Welcome!']);
        $apiServiceMock->shouldReceive('sendFlowMessage')->once();

        // 2. Act
        $handler = new WhatsAppMessageHandler($apiServiceFactoryMock, $flowRendererMock);
        $handler->process($payload);

        // 3. Assert
        $this->assertDatabaseHas('whatsapp_sessions', [
            'phone' => '98765',
            'provider_id' => $provider->id,
        ]);
    }

    protected function createWebhookPayload(string $phoneNumberId, string $from, string $text): array
    {
        return [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'metadata' => ['phone_number_id' => $phoneNumberId],
                        'messages' => [[
                            'from' => $from,
                            'text' => ['body' => $text],
                            'type' => 'text',
                        ]],
                    ],
                ]],
            ]],
        ];
    }

    protected function createFlowReplyWebhookPayload(string $phoneNumberId, string $from, array $responseData): array
    {
        return [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'metadata' => ['phone_number_id' => $phoneNumberId],
                        'messages' => [[
                            'from' => $from,
                            'interactive' => [
                                'nfm_reply' => [
                                    'response_json' => json_encode($responseData),
                                ],
                            ],
                            'type' => 'interactive',
                        ]],
                    ],
                ]],
            ]],
        ];
    }
}
