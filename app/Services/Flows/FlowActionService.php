<?php

namespace App\Services\Flows;

use App\Models\ProviderCredential;
use App\Models\WhatsappSession;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Crypt;
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
        $provider = $session->provider;

        // Interpolate URL, headers, and body
        $method = strtolower($config['method'] ?? 'POST');
        $url = $this->interpolate($config['url'] ?? '', $context);
        $headers = $this->interpolateArray($config['headers'] ?? [], $context);
        $body = $this->interpolateArray($config['body'] ?? [], $context);
        $saveTo = $config['save_to'] ?? 'api_data';

        if (! $url) {
            Log::warning('api_call action is missing a URL', ['session_id' => $session->id]);
            return $config['on_failure'] ?? null;
        }

        // Automatically add auth headers from provider credentials
        if ($provider && $provider->auth_type !== 'none') {
            $keyName = $provider->auth_type === 'bearer' ? 'bearer_token' : 'api_key';
            $credential = ProviderCredential::where('provider_id', $provider->id)
                ->where('key_name', $keyName)
                ->first();

            if ($credential) {
                try {
                    $secret = Crypt::decryptString($credential->secret_encrypted);
                    if ($provider->auth_type === 'bearer') {
                        $headers['Authorization'] = "Bearer {$secret}";
                    } elseif ($provider->auth_type === 'apikey') {
                        // Assuming the key name is the header name, e.g., X-API-Key
                        $headers[$credential->key_name] = $secret;
                    }
                } catch (\Throwable $e) {
                    Log::error('Failed to decrypt provider credential', ['credential_id' => $credential->id]);
                }
            }
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
                $status = $response->status();
                Log::error('api_call action failed', [
                    'session_id' => $session->id,
                    'status' => $status,
                    'response' => $response->body(),
                ]);

                // Save error response to context if configured
                if ($saveErrorTo = $config['save_error_to'] ?? null) {
                    $context[$saveErrorTo] = $response->json();
                    $session->update(['context' => $context]);
                }

                // Return screen for specific status code, or generic failure screen
                return $config["on_error_{$status}"] ?? $config['on_failure'] ?? null;
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
            return $config['on_failure'] ?? null;
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
