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
        $context = $session->context ?? [];

        // Interpolate URL, headers, and body
        $method = strtolower($config['method'] ?? 'POST');
        $url = $this->interpolate($config['url'] ?? '', $context);
        $headers = $this->interpolateArray($config['headers'] ?? [], $context);
        $body = $this->interpolateArray($config['body'] ?? [], $context);
        $saveTo = $config['save_to'] ?? 'api_data';

        if (! $url) {
            Log::warning('api_call action is missing a URL', ['session_id' => $session->id]);
            return $action['on_failure'] ?? null;
        }

        try {
            $response = $this->http
                ->withHeaders($headers)
                ->send($method, $url, ['json' => $body]);

            Log::info('API call response', [
                'session_id' => $session->id,
                'status' => $response->status(),
                'failed' => $response->failed(),
                'json' => $response->json(),
            ]);

            if ($response->failed()) {
                Log::error('api_call action failed', [
                    'session_id' => $session->id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return $config['on_failure'] ?? null;
            }

            $context[$saveTo] = $response->json();
            $session->update(['context' => $context]);

            $onSuccess = $config['on_success'] ?? null;
            Log::info('API call success', ['session_id' => $session->id, 'on_success' => $onSuccess]);

            return $onSuccess;
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
