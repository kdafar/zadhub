<?php

namespace Tests\Unit;

use App\Models\WhatsappSession;
use App\Services\Flows\FlowActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FlowActionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_executes_an_api_call_with_dynamic_data()
    {
        // 1. Arrange
        Http::fake([
            'https://example.com/api/users/123' => Http::response(['status' => 'ok', 'user_id' => 123]),
        ]);

        $session = WhatsappSession::factory()->create([
            'context' => [
                'user' => ['id' => 123],
                'auth' => ['token' => 'bearer-token-abc'],
                'order' => ['id' => 'xyz-456'],
            ],
        ]);

        $actions = [
            [
                'type' => 'api_call',
                'config' => [
                    'method' => 'PUT',
                    'url' => 'https://example.com/api/users/{{user.id}}',
                    'headers' => [
                        'Authorization' => 'Bearer {{auth.token}}',
                        'X-Request-ID' => 'static-id-789',
                    ],
                    'body' => [
                        'order_id' => '{{order.id}}',
                        'status' => 'processed',
                    ],
                    'save_to' => 'api_response',
                    'on_success' => 'USER_UPDATED',
                    'on_failure' => 'API_ERROR',
                ],
            ],
        ];

        // 2. Act
        $actionService = $this->app->make(FlowActionService::class);
        $nextScreenId = $actionService->executeActions($actions, $session);

        // 3. Assert
        Http::assertSent(function ($request) {
            return $request->method() === 'PUT' &&
                   $request->url() === 'https://example.com/api/users/123' &&
                   $request->hasHeader('Authorization', 'Bearer bearer-token-abc') &&
                   $request['order_id'] === 'xyz-456' &&
                   $request['status'] === 'processed';
        });

        $session->refresh();
        $this->assertEquals(['status' => 'ok', 'user_id' => 123], $session->context['api_response']);
        $this->assertEquals('USER_UPDATED', $nextScreenId);
    }
}
