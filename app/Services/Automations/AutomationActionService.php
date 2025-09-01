<?php

namespace App\Services\Automations;

use App\Models\AutomationStep;
use App\Models\Flow;
use App\Models\ProviderCredential;
use App\Models\WhatsappSession;
use App\Services\WhatsAppApiServiceFactory;
use App\Services\WhatsAppMessageHandler;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Netflie\WhatsAppCloudApi\Message\Template\Component;

class AutomationActionService
{
    public function __construct(
        protected WhatsAppApiServiceFactory $apiFactory,
        protected WhatsAppMessageHandler $messageHandler,
        protected HttpClient $http
    ) {}

    public function execute(AutomationStep $step, string $recipientPhone, array $data): void
    {
        Log::info('Executing automation step', ['step_id' => $step->id, 'action' => $step->action_type]);

        switch ($step->action_type) {
            case 'send_message_template':
                $this->handleSendMessageTemplate($step, $recipientPhone, $data);
                break;
            case 'start_flow':
                $this->handleStartFlow($step, $recipientPhone, $data);
                break;
            case 'api_call':
                $this->handleApiCall($step, $data);
                break;
            default:
                Log::warning('Unknown automation action type.', ['type' => $step->action_type]);
                break;
        }
    }

    protected function handleSendMessageTemplate(AutomationStep $step, string $recipientPhone, array $data): void
    {
        $config = $step->action_config;
        $templateName = $config['template_name'] ?? null;
        $language = $config['language'] ?? 'en_US';

        if (! $templateName) {
            Log::error('Message template action is missing template_name', ['step_id' => $step->id]);

            return;
        }

        $provider = $step->automation->provider;
        $apiService = $this->apiFactory->make($provider);

        $variables = $config['variables'] ?? [];
        $components = null;

        if (! empty($variables)) {
            $bodyParams = [];
            foreach ($variables as $placeholder => $key) {
                $value = Arr::get($data, $key);
                if ($value !== null) {
                    $bodyParams[] = ['type' => 'text', 'text' => (string) $value];
                }
            }
            $components = new Component([], $bodyParams, []);
        }

        $apiService->sendTemplate($recipientPhone, $templateName, $language, $components);
    }

    protected function handleStartFlow(AutomationStep $step, string $recipientPhone, array $data): void
    {
        $config = $step->action_config;
        $flowId = $config['flow_id'] ?? null;

        if (! $flowId) {
            Log::error('start_flow action is missing flow_id', ['step_id' => $step->id]);

            return;
        }

        $flow = Flow::find($flowId);
        if (! $flow || ! $flow->provider) {
            Log::error('Flow not found or has no provider for start_flow action', ['flow_id' => $flowId]);

            return;
        }

        $session = WhatsappSession::firstOrCreate(
            ['phone' => $recipientPhone, 'provider_id' => $flow->provider->id],
            ['status' => 'active', 'locale' => 'en']
        );
        $session->context = $data;
        $session->save();

        $this->messageHandler->startFlow($session, $flow);
    }

    protected function handleApiCall(AutomationStep $step, array $data): void
    {
        $config = $step->action_config;
        $provider = $step->automation->provider;

        $method = strtolower($config['method'] ?? 'POST');
        $url = $this->interpolate($config['url'] ?? '', $data);
        $headers = $this->interpolateArray($config['headers'] ?? [], $data);
        $body = $this->interpolateArray($config['body'] ?? [], $data);

        if (! $url) {
            Log::warning('Automation api_call action is missing a URL', ['step_id' => $step->id]);

            return;
        }

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
                        $headers[$credential->key_name] = $secret;
                    }
                } catch (\Throwable $e) {
                    Log::error('Failed to decrypt provider credential for automation', ['credential_id' => $credential->id]);
                }
            }
        }

        try {
            $response = $this->http->withHeaders($headers)->send($method, $url, ['json' => $body]);

            if ($response->failed()) {
                Log::error('Automation api_call action failed', [
                    'step_id' => $step->id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::critical('Automation api_call action crashed', [
                'step_id' => $step->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function interpolate(string $text, array $context): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/', function ($matches) use ($context) {
            return Arr::get($context, $matches[1], $matches[0]);
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
