<?php

namespace App\Services;

use App\Models\Flow;
use App\Models\Provider;
use App\Models\ServiceType;
use App\Models\WhatsappSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;

class WhatsAppMessageHandler
{
    public function __construct(
        protected WhatsAppApiServiceFactory $apiServiceFactory,
        protected FlowRenderer $flowRenderer
    ) {
    }

    public function process(array $payload): void
    {
        Log::info('Incoming WhatsApp Payload:', $payload);

        $phoneNumberId = $payload['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'] ?? null;
        $messageValue = $payload['entry'][0]['changes'][0]['value']['messages'][0] ?? null;
        $from = $messageValue['from'] ?? null;

        if (! $phoneNumberId || ! $from || ! $messageValue) {
            return;
        }

        // Since we use a single WABA, we don't find the provider via phone number ID anymore.
        // The session is now the primary context carrier.
        $session = WhatsappSession::firstOrCreate(
            ['phone' => $from],
            ['status' => 'active', 'locale' => 'en', 'context' => new \Illuminate\Database\Eloquent\Casts\AsArrayObject([])]
        );

        $session->update(['last_interacted_at' => now()]);

        // The concept of a single "provider" for the message is now ambiguous until the user selects one.
        // For sending messages back, we'll use the session's provider if set, or a system default.
        $providerForSending = $session->provider ?? Provider::first();
        if (! $providerForSending) {
            Log::error('No providers found in the system to handle message sending.');
            return;
        }

        if (isset($messageValue['interactive']['nfm_reply'])) {
            $this->handleFlowReply($session, $messageValue['interactive']['nfm_reply']);
            return;
        }

        if (isset($messageValue['text']['body'])) {
            $this->handleTextMessage($session, $providerForSending, $messageValue['text']['body']);
            return;
        }
    }

    private function handleTextMessage(WhatsappSession $session, Provider $provider, string $text): void
    {
        $incomingText = strtolower(trim($text));
        if ($incomingText === '') {
            return;
        }

        // 1. If session is in a specific state (e.g., choosing a provider), handle that.
        if (($session->context['state'] ?? null) === 'selecting_provider') {
            $this->handleProviderSelection($session, $incomingText);
            return;
        }

        // 2. Try to find a service type trigger
        $serviceType = ServiceType::where('code', $incomingText)->first();
        if ($serviceType) {
            $providers = $serviceType->providers()->where('is_active', true)->get();
            if ($providers->count() === 1) {
                // If only one provider, start its default flow directly
                $firstProvider = $providers->first();
                // Associate the session with the chosen provider
                $session->provider()->associate($firstProvider)->save();

                $defaultFlow = $firstProvider->flows()->first(); // Assuming a provider has a default flow
                if ($defaultFlow) {
                    $this->startFlow($session, $defaultFlow);
                    return;
                }
            } elseif ($providers->count() > 1) {
                // If multiple providers, ask the user to choose
                $session->context = ['state' => 'selecting_provider', 'service_type_id' => $serviceType->id];
                $session->save();

                $providerList = $providers->map(fn($p, $i) => ($i + 1) . ". {$p->name}")->implode("\n");
                $message = "Please choose a provider by replying with their number:\n{$providerList}";

                $apiService = $this->apiServiceFactory->make();
                $apiService->sendTextMessage($session->phone, $message);
                return;
            }
        }

        // 3. If nothing matches, send a default message.
        $apiService = $this->apiServiceFactory->make();
        $apiService->sendTextMessage($session->phone, "Sorry, I didn't understand that. Please use a valid keyword to start.");
    }

    private function handleProviderSelection(WhatsappSession $session, string $text): void
    {
        $serviceTypeId = $session->context['service_type_id'] ?? null;
        if (! $serviceTypeId) {
            // Should not happen, but good to be defensive. Reset state.
            $session->context = [];
            $session->save();
            $this->handleTextMessage($session, Provider::first(), $text); // Pass a default provider
            return;
        }

        $providers = ServiceType::find($serviceTypeId)->providers()->where('is_active', true)->get();

        $selection = (int) trim($text) - 1; // User sees 1-based, we use 0-based index

        if ($providers->has($selection)) {
            $selectedProvider = $providers->get($selection);
            // Associate the session with the chosen provider
            $session->provider()->associate($selectedProvider)->save();

            $defaultFlow = $selectedProvider->flows()->first(); // Assuming a provider has a default flow

            if ($defaultFlow) {
                // Clear the selection state from context before starting the flow
                $session->context = [];
                $session->save();
                $this->startFlow($session, $defaultFlow);
            } else {
                $apiService = $this->apiServiceFactory->make();
                $apiService->sendTextMessage($session->phone, "Sorry, this provider does not have an active flow.");
            }
        } else {
            // Invalid selection, re-prompt
            $providerList = $providers->map(fn($p, $i) => ($i + 1) . ". {$p->name}")->implode("\n");
            $message = "Invalid selection. Please choose a provider by replying with their number:\n{$providerList}";
            $apiService = $this->apiServiceFactory->make();
            $apiService->sendTextMessage($session->phone, $message);
        }
    }

    private function startFlow(WhatsappSession $session, Flow $flow): void
    {
        $liveVersion = $flow->liveVersion()->with('metaFlow')->first();
        if (! $liveVersion) {
            Log::error("Flow ID {$flow->id} has no published version.");
            return;
        }

        if (! $liveVersion->metaFlow?->meta_flow_id) {
            Log::error("Flow Version ID {$liveVersion->id} has not been published to Meta and is missing a `meta_flow_id`.");
            return;
        }

        $screens = $liveVersion->builder_data['screens'] ?? [];
        if (empty($screens)) {
            Log::error("Flow ID {$flow->id} live version has no screens.");
            return;
        }

        $first = $screens[0];

        $session->update([
            'service_type_id' => $flow->provider?->service_type_id,
            'flow_version_id' => $liveVersion->id,
            'current_screen' => $first['id'] ?? null,
            'status' => 'active',
            'context' => new IlluminateDatabaseEloquentCastsAsArrayObject([]),
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
        $version = $session->flowVersion()->with('metaFlow')->first();

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

        $ctx = $session->context ?? [];
        $session->update(['context' => array_merge($ctx, $responseData)]);

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

        $this->endFlow($session, 'Thank you! We have received your information.');
    }

    private function executeScreen(WhatsappSession $session, array $screenConfig, ?string $errorMessage = null): void
    {
        if (! $session->provider) {
            Log::error("Session {$session->id} has no provider, cannot execute screen.");
            return;
        }

        $version = $session->flowVersion;
        $metaFlowId = $version->metaFlow?->meta_flow_id;

        if (! $metaFlowId) {
            Log::error("Flow version ID {$version->id} is not published to Meta.");
            return;
        }

        $apiService = $this->apiServiceFactory->make();
        $screenData = $this->flowRenderer->renderScreen($screenConfig, $session->context ?? [], $errorMessage);

        $apiService->sendFlowMessage(
            $session->phone,
            $metaFlowId,
            (string) Str::uuid(),
            $screenData
        );
    }

    private function endFlow(WhatsappSession $session, ?string $message = null): void
    {
        if ($message) {
            $apiService = $this->apiServiceFactory->make();
            $apiService->sendTextMessage($session->phone, $message);
        }

        $this->pushHistory($session, 'completed');

        $session->update([
            'status' => 'completed',
            'ended_at' => now(),
            'ended_reason' => 'normal',
        ]);
    }
    }

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
