<?php

namespace App\Services;

use App\Models\Flow;
use App\Models\Provider;
use App\Models\WhatsappSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppMessageHandler
{
    public function process(array $payload): void
    {
        Log::info('Incoming WhatsApp Payload:', $payload);

        $phoneNumberId = $payload['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'] ?? null;
        $messageValue = $payload['entry'][0]['changes'][0]['value']['messages'][0] ?? null;
        $from = $messageValue['from'] ?? null;

        if (!$phoneNumberId || !$from || !$messageValue) return;

        $provider = Provider::where('whatsapp_phone_number_id', $phoneNumberId)->first();
        if (!$provider) return;

        $session = WhatsappSession::firstOrCreate(
            ['customer_phone_number' => $from, 'provider_id' => $provider->id],
            ['status' => 'active']
        );

        // Check for a Flow Reply FIRST
        if (isset($messageValue['interactive']['nfm_reply'])) {
            $this->handleFlowReply($session, $messageValue['interactive']['nfm_reply']);
            return;
        }

        // Handle starting a new conversation with a trigger keyword
        $incomingText = strtolower(trim($messageValue['text']['body'] ?? ''));
        if (empty($incomingText)) return;

        $flow = Flow::where('provider_id', $provider->id)
            ->where('trigger_keyword', $incomingText)
            ->where('is_active', true)->first();

        if ($flow) $this->startFlow($session, $flow);
    }

    private function startFlow(WhatsappSession $session, Flow $flow): void
    {
        $liveVersion = $flow->liveVersion;
        if (!$liveVersion || empty($liveVersion->builder_data['screens'])) {
            Log::error("Flow ID {$flow->id} has no live version or screens to start.");
            return;
        }

        $firstScreen = $liveVersion->builder_data['screens'][0];
        $session->update([
            'current_flow_id' => $flow->id,
            'current_step_uuid' => $firstScreen['id'],
            'status' => 'active',
            'data' => [],
        ]);

        $this->executeScreen($session, $firstScreen);
    }

    private function handleFlowReply(WhatsappSession $session, array $replyData): void
    {
        $flow = $session->currentFlow;
        if (!$flow || !$flow->liveVersion) return;

        $responseData = json_decode($replyData['response_json'], true);
        $currentScreenId = $session->current_step_uuid;
        $screens = $flow->liveVersion->builder_data['screens'];
        $currentScreen = collect($screens)->firstWhere('id', $currentScreenId);
        if (!$currentScreen) return;

        // Validation Logic
        $rules = [];
        foreach ($currentScreen['children'] ?? [] as $component) {
            $componentClass = $this->getComponentClass($component['type']);
            if ($componentClass) {
                $rules = array_merge($rules, $componentClass::getValidationRules($component['data']));
            }
        }
        $validator = \Illuminate\Support\Facades\Validator::make($responseData, $rules);
        if ($validator->fails()) {
            $this->executeScreen($session, $currentScreen, $validator->errors()->first());
            return;
        }

        // Save validated data
        $sessionData = $session->data ?? [];
        $session->update(['data' => array_merge($sessionData, $responseData)]);
        
        // Find and execute the next screen
        $nextScreenId = $currentScreen['data']['next_screen_id'] ?? null;
        if ($nextScreenId) {
            $nextScreen = collect($screens)->firstWhere('id', $nextScreenId);
            if ($nextScreen) {
                $session->update(['current_step_uuid' => $nextScreen['id']]);
                $this->executeScreen($session, $nextScreen);
                return;
            }
        }
        
        // If no next screen, end the flow
        $this->endFlow($session, "Thank you! We have received your information.");
    }

    private function executeScreen(WhatsappSession $session, array $screenConfig, ?string $errorMessage = null): void
    {
        $provider = $session->provider;
        if (empty($provider->api_token) || empty($provider->whatsapp_phone_number_id)) {
            Log::error("Provider {$provider->id} is missing API credentials.");
            return;
        }

        $apiService = new WhatsAppApiService($provider->api_token, $provider->whatsapp_phone_number_id);
        $renderer = new FlowRenderer();
        
        $screenData = $renderer->renderScreen($screenConfig, $session->data ?? [], $errorMessage);

        $apiService->sendFlowMessage(
            $session->customer_phone_number,
            config('services.whatsapp.flow_id'),
            (string) Str::uuid(),
            $screenData
        );
    }

    private function endFlow(WhatsappSession $session, ?string $message = null): void
    {
        if ($message) {
            $provider = $session->provider;
            $apiService = new WhatsAppApiService($provider->api_token, $provider->whatsapp_phone_number_id);
            $apiService->sendTextMessage($session->customer_phone_number, $message);
        }
        $session->update(['current_flow_id' => null, 'current_step_uuid' => null, 'status' => 'completed']);
    }

    private function getComponentClass(string $key): ?string
    {
        $map = [
            'text_body' => \App\FlowComponents\TextBody::class,
            'dropdown' => \App\FlowComponents\Dropdown::class,
            'text_input' => \App\FlowComponents\TextInput::class,
            'image' => \App\FlowComponents\Image::class,
            'date_picker' => \App\FlowComponents\DatePicker::class,
        ];
        return $map[$key] ?? null;
    }
}