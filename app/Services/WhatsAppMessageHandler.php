<?php

namespace App\Services;

use App\Models\Flow;
use App\Models\FlowVersion;
use App\Models\Provider;
use App\Models\WhatsappSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;

class WhatsAppMessageHandler
{
    public function process(array $payload): void
    {
        Log::info('Incoming WhatsApp Payload:', $payload);

        $phoneNumberId = $payload['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'] ?? null;
        $messageValue = $payload['entry'][0]['changes'][0]['value']['messages'][0] ?? null;
        $from = $messageValue['from'] ?? null;

        if (! $phoneNumberId || ! $from || ! $messageValue) {
            return;
        }

        $provider = Provider::where('whatsapp_phone_number_id', $phoneNumberId)->first();
        if (! $provider) {
            return;
        }

        // ⚠️ use 'phone' column (not customer_phone_number)
        $session = WhatsappSession::firstOrCreate(
            ['phone' => $from, 'provider_id' => $provider->id],
            ['status' => 'active', 'locale' => 'en']
        );

        // last seen
        $session->update(['last_interacted_at' => now()]);

        // 1) Flow reply takes precedence
        if (isset($messageValue['interactive']['nfm_reply'])) {
            $this->handleFlowReply($session, $messageValue['interactive']['nfm_reply']);

            return;
        }

        // 2) Trigger keywords
        $incomingText = strtolower(trim($messageValue['text']['body'] ?? ''));
        if ($incomingText === '') {
            return;
        }

        $flow = Flow::where('provider_id', $provider->id)
            ->where('trigger_keyword', $incomingText)
            ->where('is_active', true)
            ->first();

        if ($flow) {
            $this->startFlow($session, $flow);
        }
    }

    private function startFlow(WhatsappSession $session, Flow $flow): void
    {
        $liveVersion = $flow->liveVersion()->first(); // latest published
        if (! $liveVersion) {
            Log::error("Flow ID {$flow->id} has no published version.");

            return;
        }

        $screens = $liveVersion->builder_data['screens'] ?? [];
        if (empty($screens)) {
            Log::error("Flow ID {$flow->id} live version has no screens.");

            return;
        }

        $first = $screens[0];

        // align to your session columns
        $session->update([
            'service_id' => $flow->provider?->service_id,
            'flow_version_id' => $liveVersion->id,
            'current_screen' => $first['id'] ?? null,
            'status' => 'active',
            'context' => new \Illuminate\Database\Eloquent\Casts\AsArrayObject([]),
        ]);

        $this->pushHistory($session, 'started', [
            'flow_id' => $flow->id,
            'flow_version_id' => $liveVersion->id,
            'next' => $first['id'] ?? null,
        ]);

        $this->executeScreen($session, $first);
    }

    private function handleFlowReply(WhatsappSession $session, array $replyData): void
    {
        // load version by flow_version_id
        $version = $session->flow_version_id
            ? FlowVersion::find($session->flow_version_id)
            : null;

        if (! $version) {
            Log::warning('No flow_version for session', ['session_id' => $session->id]);

            return;
        }

        $responseData = json_decode($replyData['response_json'] ?? '{}', true) ?: [];
        $currentScreenId = $session->current_screen;
        $screens = $version->builder_data['screens'] ?? [];
        $currentScreen = collect($screens)->firstWhere('id', $currentScreenId);

        if (! $currentScreen) {
            Log::warning('Current screen not found', ['session_id' => $session->id, 'screen' => $currentScreenId]);

            return;
        }

        // Build validation rules from children
        $rules = [];
        foreach ($currentScreen['children'] ?? [] as $component) {
            $class = $this->getComponentClass($component['type'] ?? '');
            if ($class && method_exists($class, 'getValidationRules')) {
                $rules = array_merge($rules, $class::getValidationRules($component['data'] ?? []));
            }
        }

        $validator = Validator::make($responseData, $rules);
        if ($validator->fails()) {
            $this->pushHistory($session, 'validation_failed', ['error' => $validator->errors()->first()]);
            $this->sendValidationError($session, $currentScreen, $validator->errors());

            return;
        }

        // Save into context (JSON column)
        $ctx = $session->context ?? [];
        $session->update(['context' => array_merge($ctx, $responseData)]);

        // Decide next screen id (choice → static → sequential)
        $nextId = $this->resolveNextScreenId($currentScreen, $screens, $responseData);

        if ($nextId) {
            $next = collect($screens)->firstWhere('id', $nextId);
            if ($next) {
                $session->update(['current_screen' => $next['id']]);
                $this->pushHistory($session, 'screen_changed', ['to' => $next['id']]);
                $this->executeScreen($session, $next);

                return;
            }
        }

        // No next → end
        $this->endFlow($session, 'Thank you! We have received your information.');
    }

    private function executeScreen(WhatsappSession $session, array $screenConfig, ?string $errorMessage = null): void
    {
        $provider = $session->provider;
        if (empty($provider->whatsapp_phone_number_id)) {
            Log::error("Provider {$provider->id} missing WhatsApp number id.");

            return;
        }

        // If you want to skip real sends in staging, gate it here by config(...)
        $apiService = new WhatsAppApiService($provider->api_token ?? '', $provider->whatsapp_phone_number_id);
        $renderer = new FlowRenderer;

        // pass CONTEXT (your schema), not data
        $screenData = $renderer->renderScreen($screenConfig, $session->context ?? [], $errorMessage);

        // Send WhatsApp Flow message (adjust if your service differs)
        $apiService->sendFlowMessage(
            $session->phone,                         // you store phone in 'phone'
            (string) config('services.whatsapp.flow_id'),
            (string) Str::uuid(),
            $screenData
        );
    }

    private function endFlow(WhatsappSession $session, ?string $message = null): void
    {
        if ($message) {
            $provider = $session->provider;
            $apiService = new WhatsAppApiService($provider->api_token ?? '', $provider->whatsapp_phone_number_id);
            $apiService->sendTextMessage($session->phone, $message);
        }

        $this->pushHistory($session, 'completed');

        $session->update([
            'status' => 'completed',
            'ended_at' => now(),
            'ended_reason' => 'normal',
        ]);
    }

    /**
     * Decide next screen id using:
     * 1) per-choice map: $current['data']['next_on_choice'][<value>]
     * 2) static id:      $current['data']['next_screen_id']
     * 3) sequential fallback: next in $screens[]
     */
    private function resolveNextScreenId(array $current, array $screens, array $responseData): ?string
    {
        $choiceMap = $current['data']['next_on_choice'] ?? null;
        if (is_array($choiceMap)) {
            foreach ($responseData as $value) {
                $val = is_array($value) ? ($value['value'] ?? null) : $value;
                if ($val !== null && array_key_exists((string) $val, $choiceMap)) {
                    $target = (string) $choiceMap[(string) $val];
                    if ($target !== '') {
                        return $target;
                    }
                }
            }
        }

        if (! empty($current['data']['next_screen_id'])) {
            return (string) $current['data']['next_screen_id'];
        }

        $ids = array_map(fn ($s) => $s['id'] ?? null, $screens);
        $idx = array_search($current['id'] ?? null, $ids, true);
        if ($idx !== false && isset($screens[$idx + 1]['id'])) {
            return (string) $screens[$idx + 1]['id'];
        }

        return null;
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

    private function sendValidationError(WhatsappSession $session, array $currentScreen, MessageBag $errors): void
    {
        $this->executeScreen($session, $currentScreen, $errors->first());
    }

    private function pushHistory(WhatsappSession $s, string $event, array $meta = []): void
    {
        $history = $s->flow_history ?? [];
        $history[] = [
            'at' => now()->toIso8601String(),
            'event' => $event,
            'screen' => $s->current_screen ?? null,
            'meta' => $meta,
        ];
        $s->flow_history = $history;
        $s->save();
    }
}
