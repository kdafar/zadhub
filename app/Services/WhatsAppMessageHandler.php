<?php

namespace App\Services;

use App\Models\Flow;
use App\Models\Provider;
use App\Models\ServiceType;
use App\Models\WhatsappSession;
use App\Services\Flows\FlowEngine;
use App\Services\WhatsApp\TriggerResolver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;

class WhatsAppMessageHandler
{
    public function __construct(
        protected WhatsAppApiServiceFactory $apiServiceFactory,
        protected FlowRenderer $flowRenderer,
        protected TriggerResolver $triggers
    ) {}

    public function process(array $payload): void
    {
        Log::info('Incoming WhatsApp Payload:', $payload);

        $messageValue = $payload['entry'][0]['changes'][0]['value']['messages'][0] ?? null;
        $from = $messageValue['from'] ?? null;

        if (! $from || ! $messageValue) {
            return;
        }

        $session = WhatsappSession::firstOrCreate(
            ['phone' => $from],
            [
                'status' => 'active',
                'locale' => 'en',
                'context' => [],
            ]
        );

        $session->update(['last_interacted_at' => now()]);

        if (isset($messageValue['interactive']['nfm_reply'])) {
            $this->handleFlowReply($session, $messageValue['interactive']['nfm_reply']);

            return;
        }

        if (isset($messageValue['text']['body'])) {
            $providerForSending = $session->provider ?? Provider::first();
            if (! $providerForSending) {
                Log::error('No providers found to handle message sending.');

                return;
            }
            $this->handleTextMessage($session, $providerForSending, $messageValue['text']['body']);
        }
    }

    private function handleTextMessage(WhatsappSession $session, Provider $provider, string $text): void
    {
        $incomingText = strtolower(trim($text));
        if ($incomingText === '') {
            return;
        }

        if (($session->context['state'] ?? null) === 'selecting_provider') {
            $this->handleProviderSelection($session, $incomingText);

            return;
        }

        $trigger = $this->triggers->resolve($incomingText);
        if ($trigger) {
            $flowVersion = $trigger->use_latest_published
                ? ($trigger->provider ?? $trigger->serviceType)?->flows()->first()?->liveVersion
                : $trigger->flowVersion;

            if ($flowVersion && $flowVersion->flow) {
                $session->provider()->associate($flowVersion->flow->provider)->save();
                $this->startFlow($session, $flowVersion->flow);

                return;
            }
        }

        $this->apiServiceFactory->make($provider)->sendTextMessage(
            $session->phone,
            "I couldn't understand your message. Please reply with a valid keyword to continue."
        );
    }

    private function handleProviderSelection(WhatsappSession $session, string $text): void
    {
        $serviceTypeId = $session->context['service_type_id'] ?? null;
        if (! $serviceTypeId) {
            $session->context = [];
            $session->save();
            if ($fallbackProvider = Provider::first()) {
                $this->handleTextMessage($session, $fallbackProvider, $text);
            }

            return;
        }

        $providers = ServiceType::find($serviceTypeId)?->providers()->where('is_active', true)->orderBy('name')->get() ?? collect();
        $selectionIndex = max(0, ((int) trim($text)) - 1);

        if ($providers->has($selectionIndex)) {
            $selectedProvider = $providers->get($selectionIndex);
            $session->provider()->associate($selectedProvider)->save();

            $defaultFlow = $selectedProvider->flows()->first();
            if ($defaultFlow) {
                $session->context = [];
                $session->save();
                $this->startFlow($session, $defaultFlow);

                return;
            }

            $this->apiServiceFactory->make($selectedProvider)->sendTextMessage($session->phone, 'Sorry, this provider does not have an active flow.');

            return;
        }

        $providerList = $providers->values()->map(fn ($p, $i) => ($i + 1).'. {$p->name}')->implode("\n");
        $message = "Invalid selection. Please choose a provider by replying with their number:\n{$providerList}";
        $this->apiServiceFactory->make($session->provider)->sendTextMessage($session->phone, $message);
    }

    private function startFlow(WhatsappSession $session, Flow $flow): void
    {
        $liveVersion = $flow->liveVersion()->with('metaFlow')->first();
        if (! $liveVersion) {
            Log::error("Flow ID {$flow->id} has no published version.");

            return;
        }

        if (! $liveVersion->metaFlow?->meta_flow_id) {
            Log::error("Flow Version ID {$liveVersion->id} has not been published to Meta (missing meta_flow_id).");

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
            'context' => [],
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
        foreach (($currentScreen['children'] ?? []) as $component) {
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

        $ctx = (array) ($session->context ?? []);
        $session->update(['context' => array_merge($ctx, $responseData)]);

        $nextId = FlowEngine::determineNextScreenId($version->definition, $currentScreen, $responseData, $ctx);

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
        $metaFlowId = $version?->metaFlow?->meta_flow_id;

        if (! $metaFlowId) {
            Log::error("Flow version ID {$version->id} is not published to Meta (no meta_flow_id).");

            return;
        }

        $apiService = $this->apiServiceFactory->make($session->provider);
        $screenData = $this->flowRenderer->renderScreen($screenConfig, (array) ($session->context ?? []), $errorMessage);

        $apiService->sendFlowMessage($session->phone, $metaFlowId, (string) Str::uuid(), $screenData);
    }

    private function endFlow(WhatsappSession $session, ?string $message = null): void
    {
        if ($message) {
            $this->apiServiceFactory->make($session->provider)->sendTextMessage($session->phone, $message);
        }

        $this->pushHistory($session, 'completed');

        $session->update([
            'status' => 'completed',
            'ended_at' => now(),
            'ended_reason' => 'normal',
        ]);
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
        $history = (array) ($s->flow_history ?? []);
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
