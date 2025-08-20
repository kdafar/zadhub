<?php

namespace Tests\Unit;

use App\Models\Flow;
use App\Models\FlowVersion;
use App\Models\MetaFlow;
use App\Models\Provider;
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

    public function test_it_starts_a_flow_when_a_trigger_keyword_is_received()
    {
        // 1. Arrange
        $provider = Provider::factory()->create(['whatsapp_phone_number_id' => '12345', 'api_token' => 'fake-token']);
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
        $provider = Provider::factory()->create(['whatsapp_phone_number_id' => '12345', 'api_token' => 'fake-token']);
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