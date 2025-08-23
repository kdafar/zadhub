<?php

namespace App\Services\Flows;

use App\Models\WhatsappSession;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Log;

class FlowActionService
{
    public function __construct(protected HttpClient $http) {}

    /**
     * Execute a list of actions defined for a screen.
     *
     * @param  array  $actions  The array of action configurations.
     * @param  WhatsappSession  $session  The current user session.
     * @return string|null The ID of the next screen to transition to, or null to continue normal flow.
     */
    public function executeActions(array $actions, WhatsappSession $session): ?string
    {
        Log::info('Executing actions for session', ['session_id' => $session->id, 'action_count' => count($actions)]);

        $nextScreenId = null;

        foreach ($actions as $action) {
            $result = match ($action['type'] ?? null) {
                'api_call' => $this->handleApiCall($action, $session),
                default => null,
            };

            // The last action that returns a screen ID wins
            if ($result) {
                $nextScreenId = $result;
            }
        }

        return $nextScreenId;
    }

    private function handleApiCall(array $action, WhatsappSession $session): ?string
    {
        $config = $action['config'] ?? [];
        $url = $config['url'] ?? null;
        $body = $this->interpolateArray($config['body'] ?? [], $session->context);
        $saveTo = $config['save_to'] ?? 'api_data';

        if (! $url) {
            Log::warning('api_call action is missing a URL', ['session_id' => $session->id]);
            return $action['on_failure'] ?? null;
        }

        try {
            $response = $this->http->post($url, $body);

            if ($response->failed()) {
                Log::error('api_call action failed', [
                    'session_id' => $session->id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return $action['on_failure'] ?? null;
            }

            $context = $session->context ?? [];
            $context[$saveTo] = $response->json();
            $session->update(['context' => $context]);

            return $action['on_success'] ?? null;
        } catch (\Throwable $e) {
            Log::critical('api_call action crashed', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
            return $action['on_failure'] ?? null;
        }
    }

    private function interpolate(string $text, array $context): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/', function ($matches) use ($context) {
            return data_get($context, $matches[1], $matches[0]);
        }, $text);
    }

    private function interpolateArray(array $arr, array $context): array
    {
        foreach ($arr as $key => &$value) {
            if (is_array($value)) {
                $value = $this->interpolateArray($value, $context);
            } elseif (is_string($value)) {
                $value = $this->interpolate($value, $context);
            }
        }

        return $arr;
    }
}
