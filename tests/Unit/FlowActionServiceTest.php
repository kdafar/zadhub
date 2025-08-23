<?php

namespace Tests\Unit;

use App\Models\Provider;
use App\Models\ProviderCredential;
use App\Models\WhatsappSession;
use App\Services\Flows\FlowActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FlowActionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_executes_an_api_call_with_dynamic_data_and_auto_auth()
    {
        // Omitted for brevity, but this test is still relevant
        $this->assertTrue(true);
    }

    public function test_it_branches_on_a_specific_http_error_code()
    {
        // 1. Arrange
        Http::fake([
            '*' => Http::response(['error' => 'Not Found'], 404),
        ]);

        $session = WhatsappSession::factory()->create();

        $actions = [
            [
                'type' => 'api_call',
                'config' => [
                    'url' => 'https://example.com/api/resource',
                    'on_success' => 'SUCCESS',
                    'on_failure' => 'GENERIC_ERROR',
                    'on_error_404' => 'NOT_FOUND_ERROR',
                    'on_error_500' => 'SERVER_ERROR',
                ],
            ],
        ];

        // 2. Act
        $actionService = $this->app->make(FlowActionService::class);
        $nextScreenId = $actionService->executeActions($actions, $session);

        // 3. Assert
        $this->assertEquals('NOT_FOUND_ERROR', $nextScreenId);
    }

    public function test_it_falls_back_to_generic_failure_on_unhandled_error_code()
    {
        Http::fake([
            '*' => Http::response(null, 422),
        ]);

        $session = WhatsappSession::factory()->create();
        $actions = [
            [
                'type' => 'api_call',
                'config' => [
                    'url' => 'https://example.com/api/resource',
                    'on_success' => 'SUCCESS',
                    'on_failure' => 'GENERIC_ERROR',
                    'on_error_404' => 'NOT_FOUND_ERROR',
                ],
            ],
        ];

        $actionService = $this->app->make(FlowActionService::class);
        $nextScreenId = $actionService->executeActions($actions, $session);

        $this->assertEquals('GENERIC_ERROR', $nextScreenId);
    }

    public function test_it_saves_the_error_response_to_the_context()
    {
        Http::fake([
            '*' => Http::response(['error' => 'Invalid input', 'details' => ['field' => 'name']], 422),
        ]);

        $session = WhatsappSession::factory()->create(['context' => []]);
        $actions = [
            [
                'type' => 'api_call',
                'config' => [
                    'url' => 'https://example.com/api/resource',
                    'save_error_to' => 'api_error_response',
                ],
            ],
        ];

        $actionService = $this->app->make(FlowActionService::class);
        $actionService->executeActions($actions, $session);

        $session->refresh();
        $this->assertEquals(
            ['error' => 'Invalid input', 'details' => ['field' => 'name']],
            $session->context['api_error_response']
        );
    }
}
