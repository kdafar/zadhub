<?php

namespace App\Services;

use App\Models\Flow;
use App\Models\Provider;
use App\Models\ServiceType;
use App\Models\WhatsappSession;
use App\Services\Flows\FlowActionService;
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
        protected TriggerResolver $triggers,
        protected FlowActionService $actionService
    ) {}

    public function process(array $payload, Provider $provider): void
    {
        Log::info('Incoming WhatsApp message received.');

        $change = $payload['entry'][0]['changes'][0]['value'] ?? null;
        $messageValue = $change['messages'][0] ?? null;
        $from = $messageValue['from'] ?? null;

        if (! $change) {
            Log::warning('Webhook received without a "value" object.', ['payload' => $payload]);

            return;
        }

        // Handle non-message events like status updates, quality updates etc.
        if (! $messageValue || ! $from) {
            Log::info('Received a non-message webhook event.', ['event' => $change['event'] ?? 'unknown', 'provider_id' => $provider->id]);

            return;
        }

        $session = WhatsappSession::firstOrCreate(
            ['phone' => $from, 'provider_id' => $provider->id],
            ['status' => 'active', 'locale' => 'en', 'context' => []]
        );

        if (! $session->provider) {
            $session->provider()->associate($provider)->save();
        }

        $session->update(['last_interacted_at' => now()]);

        if (isset($messageValue['interactive']['nfm_reply'])) {
            $this->handleFlowReply($session, $messageValue['interactive']['nfm_reply']);

            return;
        }

        if (isset($messageValue['text']['body'])) {
            $this->handleTextMessage($session, $messageValue['text']['body']);
        }
    }

    private function handleTextMessage(WhatsappSession $session, string $text): void
    {
        $incomingText = strtolower(trim($text));
        if ($incomingText === '') {
            return;
        }

        $provider = $session->provider;
        if (! $provider) {
            Log::error("Could not handle text message: Session {$session->id} has no provider.");

            return;
        }

        $trigger = $this->triggers->resolve($incomingText, $provider->id);

        if ($trigger) {
            Log::info('Trigger found.', ['trigger_id' => $trigger->id, 'use_latest' => $trigger->use_latest_published]);

            $flowVersion = $trigger->use_latest_published
                ? $provider->flows()->first()?->liveVersion
                : $trigger->flowVersion;

            Log::info('FlowVersion resolution.', [
                'found_version_id' => $flowVersion?->id,
                'flow_id_on_version' => $flowVersion?->flow_id,
            ]);

            if ($flowVersion && $flowVersion->flow) {
                Log::info('FlowVersion and Flow are valid, starting flow.', ['flow_id' => $flowVersion->flow->id]);
                $this->startFlow($session, $flowVersion->flow);

                return;
            } else {
                Log::error('Could not start flow. FlowVersion or its associated Flow is missing.', [
                    'trigger_id' => $trigger->id,
                    'flow_version_id' => $flowVersion?->id,
                    'has_flow' => ! empty($flowVersion?->flow),
                ]);
            }
        }

        // Fetch available keywords for the provider to show in the fallback message
        $keywords = $provider->flows()->where('is_active', true)->pluck('trigger_keyword')->filter()->unique()->values();
        $this->sendSystemMessage($session, 'fallback', ['keywords' => $keywords->implode(', ')]);
    }

    private function handleProviderSelection(WhatsappSession $session, string $text): void
    {
        $serviceTypeId = $session->context['service_type_id'] ?? null;
        if (! $serviceTypeId) {
            $session->context = [];
            $session->save();
            $this->handleTextMessage($session, $text);

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

            $this->sendSystemMessage($session, 'provider_not_ready');

            return;
        }

        $providerList = $providers->values()->map(fn ($p, $i) => ($i + 1).'. '.$p->name)->implode("\n");
        $this->sendSystemMessage($session, 'invalid_provider_selection', ['provider_list' => $providerList]);
    }

    private function startFlow(WhatsappSession $session, Flow $flow): void
    {
        Log::info('startFlow() invoked', ['flow_id' => $flow->id, 'session_id' => $session->id]);

        try {
            $liveVersion = $flow->liveVersion()->with('metaFlow')->first();
            if (! $liveVersion) {
                Log::error("Flow ID {$flow->id} has no published version.", ['session_id' => $session->id]);
                $this->sendSystemMessage($session, 'flow_not_ready');

                return;
            }

            if (! $liveVersion->metaFlow?->meta_flow_id) {
                Log::error("Flow Version ID {$liveVersion->id} has not been published to Meta.", ['session_id' => $session->id]);
                $this->sendSystemMessage($session, 'flow_not_ready');

                return;
            }

            $def = (array) ($liveVersion->definition ?? []) ?: (array) ($liveVersion->builder_data ?? []);
            $startId = $def['start_screen'] ?? null;
            $screens = $this->normalizeScreens($def['screens'] ?? []);
            $firstScreen = collect($screens)->first(fn ($s) => ($s['id'] ?? null) === $startId);

            if (! $firstScreen) {
                Log::error("Flow version ID {$liveVersion->id} has no valid start screen.", ['session_id' => $session->id]);
                $this->sendSystemMessage($session, 'flow_not_ready');

                return;
            }

            $session->update([
                'service_type_id' => $flow->provider?->service_type_id,
                'flow_version_id' => $liveVersion->id,
                'current_screen' => $startId,
                'status' => 'active',
                'context' => [],
            ]);

            $session->refresh();
            $this->pushHistory($session, 'started', ['flow_id' => $flow->id, 'next' => $startId]);

            $actions = $firstScreen['actions'] ?? [];
            if (! empty($actions)) {
                $nextId = $this->actionService->executeActions($actions, $session);
                if ($nextId && ($nextScreen = collect($screens)->firstWhere('id', $nextId))) {
                    $session->update(['current_screen' => $nextId]);
                    $this->pushHistory($session, 'action_redirect', ['to' => $nextId]);
                    $this->executeScreen($session, $nextScreen);

                    return;
                }
            }

            $this->executeScreen($session, $firstScreen);
        } catch (\Throwable $e) {
            Log::error('startFlow crashed', ['flow_id' => $flow->id, 'session_id' => $session->id, 'err' => $e->getMessage()]);
        }
    }

    private function handleFlowReply(WhatsappSession $session, array $replyData): void
    {
        $version = $session->flowVersion()->with('metaFlow')->first();
        if (! $version) {
            Log::warning('No flow_version for session', ['session_id' => $session->id]);

            return;
        }

        $responseData = json_decode($replyData['response_json'] ?? '{}', true) ?: [];
        $def = (array) ($version->definition ?? []) ?: (array) ($version->builder_data ?? []);
        $screens = $this->normalizeScreens($def['screens'] ?? []);
        $currentScreen = collect($screens)->first(fn ($s) => ($s['id'] ?? null) === $session->current_screen);

        if (! $currentScreen) {
            Log::warning('Current screen not found', ['session_id' => $session->id, 'screen' => $session->current_screen]);

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

        if ($nextId && ($next = collect($screens)->firstWhere('id', $nextId))) {
            $session->update(['current_screen' => $next['id']]);
            $this->pushHistory($session, 'screen_changed', ['to' => $next['id']]);

            $actions = $next['actions'] ?? [];
            if (! empty($actions)) {
                $finalNextId = $this->actionService->executeActions($actions, $session);
                if ($finalNextId && ($finalScreen = collect($screens)->firstWhere('id', $finalNextId))) {
                    $session->update(['current_screen' => $finalNextId]);
                    $this->pushHistory($session, 'action_redirect', ['to' => $finalNextId]);
                    $this->executeScreen($session, $finalScreen);

                    return;
                }
            }

            $this->executeScreen($session, $next);

            return;
        }

        $this->endFlow($session);
    }

    private function executeScreen(WhatsappSession $session, array $screenConfig, ?string $errorMessage = null): void
    {
        if (! $session->provider) {
            Log::error("Session {$session->id} has no provider, cannot execute screen.");

            return;
        }

        try {
            $version = $session->flowVersion()->with('metaFlow')->first();
            $metaFlowId = $version?->metaFlow?->meta_flow_id;

            if (! $metaFlowId) {
                Log::error('Flow version missing or not published to Meta.', ['session_id' => $session->id, 'version_id' => $version?->id]);
                $this->apiServiceFactory->make($session->provider)->sendTextMessage($session->phone, $this->extractPlainText($screenConfig, $errorMessage));

                return;
            }

            $apiService = $this->apiServiceFactory->make($session->provider);
            $screenData = $this->flowRenderer->renderScreen($screenConfig, (array) ($session->context ?? []), $errorMessage);
            $apiService->sendFlowMessage($session->phone, $metaFlowId, (string) Str::uuid(), $screenData);

        } catch (\Throwable $e) {
            Log::error('executeScreen crashed', ['session_id' => $session->id, 'err' => $e->getMessage()]);
        }
    }

    private function endFlow(WhatsappSession $session): void
    {
        $this->sendSystemMessage($session, 'flow_completed');
        $this->pushHistory($session, 'completed');

        $session->update([
            'status' => 'completed',
            'ended_at' => now(),
            'ended_reason' => 'normal',
        ]);
    }

    private function sendSystemMessage(WhatsappSession $session, string $key, array $replacements = []): void
    {
        $message = $this->getSystemMessage($session, $key, $replacements);
        Log::info('Preparing to send system message.', ['session_id' => $session->id, 'key' => $key, 'message' => $message, 'has_provider' => (bool) $session->provider]);
        if ($message && $session->provider) {
            Log::info('Provider and message OK, calling API service factory.', ['session_id' => $session->id]);
            $this->apiServiceFactory->make($session->provider)->sendTextMessage($session->phone, $message);
        }
    }

    private function getSystemMessage(WhatsappSession $session, string $key, array $replacements = []): string
    {
        $serviceType = $session->provider?->serviceType;
        $locale = $session->locale ?? 'en';

        $template = data_get($serviceType?->message_templates, "{$locale}.{$key}")
            ?? data_get($serviceType?->message_templates, "en.{$key}");

        if (! $template) {
            $defaults = [
                'fallback' => "Sorry, I didn't understand. Please try one of the following options: {{keywords}}",
                'provider_not_ready' => 'Sorry, this provider does not have an active flow.',
                'invalid_provider_selection' => "Invalid selection. Please choose a provider by replying with their number:\n{{provider_list}}",
                'flow_completed' => 'Thank you! We have received your information.',
                'flow_not_ready' => 'We are sorry, but this service is not available at the moment. Please try again later.',
            ];
            $template = $defaults[$key] ?? 'An unexpected error occurred.';
        }

        foreach ($replacements as $k => $v) {
            $template = str_replace("{{{$k}}}", (string) $v, $template);
        }

        return $template;
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
        $history[] = ['at' => now()->toIso8601String(), 'event' => $event, 'screen' => $s->current_screen ?? null, 'meta' => $meta];
        $s->flow_history = $history;
        $s->save();
    }

    private function normalizeScreens(array $screens): array
    {
        if (array_is_list($screens)) {
            return array_map(fn ($s) => is_array($s) ? array_merge($s, ['id' => $s['id'] ?? ($s['data']['id'] ?? null)]) : $s, $screens);
        }

        return collect($screens)->map(fn ($s, $key) => is_array($s) ? array_merge($s, ['id' => $s['id'] ?? (string) $key]) : ['id' => (string) $key])->values()->all();
    }

    private function extractPlainText(array $screen, ?string $errorMessage = null): string
    {
        $prefix = $errorMessage ? ('⚠️ '.$errorMessage."\n\n") : '';
        if (($screen['type'] ?? null) === 'text_body') {
            return $prefix.((string) ($screen['data']['text'] ?? '...'));
        }
        if (($screen['type'] ?? null) === 'text') {
            return $prefix.((string) ($screen['message'] ?? '...'));
        }

        return $prefix.'Continue: {'.($screen['id'] ?? 'screen').'}';
    }
}
