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
use Illuminate\Support\MessageBag;
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
        $provider = Provider::factory()->create();
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

        // Mock dependencies
        $apiServiceMock = Mockery::mock(WhatsAppApiService::class);
        $apiServiceFactoryMock = Mockery::mock(WhatsAppApiServiceFactory::class);
        $flowRendererMock = Mockery::mock(FlowRenderer::class);

        $apiServiceFactoryMock->shouldReceive('make')->andReturn($apiServiceMock);
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
                            ['type' => 'text_input', 'data' => ['name' => 'user_name', 'is_required' => true]]
                        ],
                        'data' => ['next_screen_id' => 'THANK_YOU']
                    ],
                    [
                        'id' => 'THANK_YOU',
                        'title' => 'Thank You'
                    ]
                ]
            ]
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

        $apiServiceFactoryMock->shouldReceive('make')->andReturn($apiServiceMock);
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
        $provider1 = Provider::factory()->create(['service_type_id' => $serviceType->id, 'name' => 'Pizza Place']);
        $provider2 = Provider::factory()->create(['service_type_id' => $serviceType->id, 'name' => 'Burger Joint']);
        $flow = Flow::factory()->create(['provider_id' => $provider2->id]);
        $flowVersion = FlowVersion::factory()->create(['flow_id' => $flow->id, 'status' => 'published']);
        MetaFlow::factory()->create(['flow_version_id' => $flowVersion->id, 'meta_flow_id' => 'meta-flow-456']);

        $apiServiceMock = Mockery::mock(WhatsAppApiService::class);
        $apiServiceFactoryMock = Mockery::mock(WhatsAppApiServiceFactory::class);
        $flowRendererMock = Mockery::mock(FlowRenderer::class);

        $apiServiceFactoryMock->shouldReceive('make')->andReturn($apiServiceMock);

        // 2. Act (Part 1: User sends service keyword)
        $payload1 = $this->createWebhookPayload('111', 'user-phone', 'food');
        $apiServiceMock->shouldReceive('sendTextMessage')->once()
            ->with('user-phone', "Please choose a provider by replying with their number:\n1. {$provider1->name}\n2. {$provider2->name}");

        $handler = new WhatsAppMessageHandler($apiServiceFactoryMock, $flowRendererMock);
        $handler->process($payload1);

        // 3. Assert (Part 1: Session is in selection state)
        $this->assertDatabaseHas('whatsapp_sessions', [
            'phone' => 'user-phone',
            'context' => json_encode(['state' => 'selecting_provider', 'service_type_id' => $serviceType->id])
        ]);

        // 4. Act (Part 2: User sends provider choice)
        $payload2 = $this->createWebhookPayload('111', 'user-phone', '2'); // Chooses Burger Joint
        $flowRendererMock->shouldReceive('renderScreen')->once()->andReturn([]);
        $apiServiceMock->shouldReceive('sendFlowMessage')->once();

        $handler->process($payload2);

        // 5. Assert (Part 2: Flow has started)
        $this->assertDatabaseHas('whatsapp_sessions', [
            'phone' => 'user-phone',
            'provider_id' => $provider2->id,
            'flow_version_id' => $flowVersion->id,
            'context' => '[]' // Context is cleared
        ]);
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

    protected function createFlowReplyWebhookPayload(string $phoneNumberId, string $from, array $responseData): array
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
                                        'interactive' => [
                                            'nfm_reply' => [
                                                'response_json' => json_encode($responseData),
                                            ],
                                        ],
                                        'type' => 'interactive',
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
