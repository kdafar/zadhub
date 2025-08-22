<?php

namespace Tests\Unit;

use App\Models\Flow;
use App\Models\FlowTrigger;
use App\Models\FlowVersion;
use App\Models\MetaFlow;
use App\Models\Provider;
use App\Services\FlowRenderer;
use App\Services\WhatsApp\TriggerResolver;
use App\Services\WhatsAppApiService;
use App\Services\WhatsAppApiServiceFactory;
use App\Services\WhatsAppMessageHandler;
use App\Models\WhatsappSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WhatsAppMessageHandlerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Provider::factory()->create();
    }

    public function test_it_starts_a_flow_when_a_trigger_keyword_is_received()
    {
        // 1. Arrange
        $flow = Flow::factory()->create();
        $flowVersion = FlowVersion::factory()->create([
            'flow_id' => $flow->id,
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
        $trigger = FlowTrigger::factory()->create([
            'keyword' => 'start',
            'flow_version_id' => $flowVersion->id,
        ]);

        $payload = $this->createWebhookPayload('12345', '98765', 'start');

        // Mock dependencies
        $apiServiceMock = Mockery::mock(WhatsAppApiService::class);
        $apiServiceFactoryMock = Mockery::mock(WhatsAppApiServiceFactory::class);
        $flowRendererMock = Mockery::mock(FlowRenderer::class);
        $triggerResolverMock = Mockery::mock(TriggerResolver::class);

        $apiServiceFactoryMock->shouldReceive('make')->withAnyArgs()->andReturn($apiServiceMock);
        $flowRendererMock->shouldReceive('renderScreen')->andReturn(['id' => 'WELCOME', 'title' => 'Welcome!']);
        $apiServiceMock->shouldReceive('sendFlowMessage')->once();
        $triggerResolverMock->shouldReceive('resolve')->with('start')->andReturn($trigger);

        // 2. Act
        $handler = new WhatsAppMessageHandler($apiServiceFactoryMock, $flowRendererMock, $triggerResolverMock);
        $handler->process($payload);

        // 3. Assert
        $this->assertDatabaseHas('whatsapp_sessions', [
            'phone' => '98765',
            'provider_id' => $flow->provider_id,
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
                    'ASK_NAME' => [
                        'id' => 'ASK_NAME',
                        'title' => 'Enter Name',
                        'footer' => ['next' => 'THANK_YOU'],
                    ],
                    'THANK_YOU' => [
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
        $triggerResolverMock = Mockery::mock(TriggerResolver::class);

        $apiServiceFactoryMock->shouldReceive('make')->withAnyArgs()->andReturn($apiServiceMock);
        $flowRendererMock->shouldReceive('renderScreen')->andReturn([]);
        $apiServiceMock->shouldReceive('sendFlowMessage')->once();

        // 2. Act
        $handler = new WhatsAppMessageHandler($apiServiceFactoryMock, $flowRendererMock, $triggerResolverMock);
        $handler->process($payload);

        // 3. Assert
        $session->refresh();
        $this->assertEquals('THANK_YOU', $session->current_screen);
        $this->assertEquals('John Doe', $session->context['user_name']);
    }

    public function test_it_handles_a_multi_step_branching_flow()
    {
        // 1. Arrange
        $provider = Provider::factory()->create();
        $flow = Flow::factory()->create(['provider_id' => $provider->id]);
        $flowVersion = FlowVersion::factory()->create([
            'flow_id' => $flow->id,
            'status' => 'published',
            'definition' => [
                'start_screen' => 'CHOOSE_PATH',
                'screens' => [
                    'CHOOSE_PATH' => [
                        'id' => 'CHOOSE_PATH',
                        'components' => [
                            ['type' => 'Dropdown', 'name' => 'path', 'options' => [
                                ['value' => 'A', 'next' => 'PATH_A'],
                                ['value' => 'B', 'next' => 'PATH_B'],
                            ]]
                        ],
                    ],
                    'PATH_A' => ['id' => 'PATH_A', 'footer' => ['next' => 'END']],
                    'PATH_B' => ['id' => 'PATH_B', 'footer' => ['next' => 'END']],
                    'END' => ['id' => 'END'],
                ],
            ],
        ]);
        MetaFlow::factory()->create(['flow_version_id' => $flowVersion->id]);

        $session = WhatsappSession::factory()->create([
            'provider_id' => $provider->id,
            'flow_version_id' => $flowVersion->id,
            'current_screen' => 'CHOOSE_PATH',
            'status' => 'active',
        ]);

        $payload = $this->createFlowReplyWebhookPayload('123', $session->phone, ['path' => 'A']);

        // Mock dependencies
        $apiServiceMock = Mockery::mock(WhatsAppApiService::class);
        $apiServiceFactoryMock = Mockery::mock(WhatsAppApiServiceFactory::class);
        $flowRendererMock = Mockery::mock(FlowRenderer::class);
        $triggerResolverMock = Mockery::mock(TriggerResolver::class);

        $apiServiceFactoryMock->shouldReceive('make')->withAnyArgs()->andReturn($apiServiceMock);
        $flowRendererMock->shouldReceive('renderScreen')->andReturn([]);
        $apiServiceMock->shouldReceive('sendFlowMessage')->once();

        // 2. Act
        $handler = new WhatsAppMessageHandler($apiServiceFactoryMock, $flowRendererMock, $triggerResolverMock);
        $handler->process($payload);

        // 3. Assert
        $session->refresh();
        $this->assertEquals('PATH_A', $session->current_screen);
        $this->assertEquals('A', $session->context['path']);
    }

    public function test_it_interpolates_context_variables_into_the_next_screen()
    {
        // 1. Arrange
        $provider = Provider::factory()->create();
        $flow = Flow::factory()->create(['provider_id' => $provider->id]);
        $flowVersion = FlowVersion::factory()->create([
            'flow_id' => $flow->id,
            'status' => 'published',
            'definition' => [
                'start_screen' => 'ASK_NAME',
                'screens' => [
                    'ASK_NAME' => [
                        'id' => 'ASK_NAME',
                        'footer' => ['next' => 'GREETING'],
                    ],
                    'GREETING' => [
                        'id' => 'GREETING',
                        'data' => [
                            'title' => 'Welcome, {{user.name}}!',
                        ],
                    ],
                ],
            ],
        ]);
        MetaFlow::factory()->create(['flow_version_id' => $flowVersion->id]);

        $session = WhatsappSession::factory()->create([
            'provider_id' => $provider->id,
            'flow_version_id' => $flowVersion->id,
            'current_screen' => 'ASK_NAME',
        ]);

        $payload = $this->createFlowReplyWebhookPayload('123', $session->phone, ['user' => ['name' => 'John']]);

        // Mock dependencies
        $apiServiceMock = Mockery::mock(WhatsAppApiService::class);
        $apiServiceFactoryMock = Mockery::mock(WhatsAppApiServiceFactory::class);
        $flowRendererMock = $this->app->make(FlowRenderer::class); // Use real renderer
        $triggerResolverMock = Mockery::mock(TriggerResolver::class);

        $apiServiceFactoryMock->shouldReceive('make')->withAnyArgs()->andReturn($apiServiceMock);
        // Assert that the rendered screen data has the interpolated title
        $apiServiceMock->shouldReceive('sendFlowMessage')->once()->with(
            Mockery::any(),
            Mockery::any(),
            Mockery::any(),
            Mockery::on(function ($screenData) {
                return $screenData['title'] === 'Welcome, John!';
            })
        );

        // 2. Act
        $handler = new WhatsAppMessageHandler($apiServiceFactoryMock, $flowRendererMock, $triggerResolverMock);
        $handler->process($payload);

        // 3. Assert
        $session->refresh();
        $this->assertEquals('GREETING', $session->current_screen);
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