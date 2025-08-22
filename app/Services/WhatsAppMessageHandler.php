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
    ) {}

    /**
     * Entry point for incoming WhatsApp webhooks.
     */
    public function process(array $payload): void
    {
        Log::info('Incoming WhatsApp Payload:', $payload);

        $messageValue = $payload['entry'][0]['changes'][0]['value']['messages'][0] ?? null;
        $from = $messageValue['from'] ?? null;

        if (! $from || ! $messageValue) {
            return;
        }

        // Primary context carrier: the session
        $session = WhatsappSession::firstOrCreate(
            ['phone' => $from],
            [
                'status' => 'active',
                'locale' => 'en',
                'context' => [],
            ]
        );

        $session->update(['last_interacted_at' => now()]);

        // Text vs NFM (flow) reply
        if (isset($messageValue['interactive']['nfm_reply'])) {
            $this->handleFlowReply($session, $messageValue['interactive']['nfm_reply']);

            return;
        }

        if (isset($messageValue['text']['body'])) {
            // Note: for plain text we don't require a provider yet;
            // provider is only required when sending a Flow message (executeScreen).
            $providerForSending = $session->provider ?? Provider::first();
            if (! $providerForSending) {
                Log::error('No providers found to handle message sending.');

                return;
            }

            $this->handleTextMessage($session, $providerForSending, $messageValue['text']['body']);
        }
    }

    /**
     * Handle plain text messages (keywords, provider selection, etc.)
     */
    private function handleTextMessage(WhatsappSession $session, Provider $provider, string $text): void
    {
        $incomingText = strtolower(trim($text));
        if ($incomingText === '') {
            return;
        }

        // 1) If user is selecting a provider, treat message as selection
        if (($session->context['state'] ?? null) === 'selecting_provider') {
            $this->handleProviderSelection($session, $incomingText);

            return;
        }

        // 2) Keyword → service type
        $serviceType = ServiceType::where('code', $incomingText)->first();
        if ($serviceType) {
            $providers = $serviceType->providers()->where('is_active', true)->orderBy('name')->get();

            if ($providers->count() === 1) {
                // Single provider → associate & start default flow
                $selectedProvider = $providers->first();
                $session->provider()->associate($selectedProvider)->save();

                $defaultFlow = $selectedProvider->flows()->first(); // your “default flow” convention
                if ($defaultFlow) {
                    $this->startFlow($session, $defaultFlow);

                    return;
                }

                $this->apiServiceFactory->make($selectedProvider)->sendTextMessage(
                    $session->phone,
                    'Sorry, this provider does not have an active flow.'
                );

                return;
            }

            if ($providers->count() > 1) {
                $session->update([
                    'status' => 'selecting_provider',
                    'context' => [
                        'state' => 'selecting_provider',
                        'service_type_id' => $serviceType->id,
                    ],
                ]);

                $providerList = $providers
                    ->values()
                    ->map(fn ($p, $i) => ($i + 1).". {$p->name}")
                    ->implode("\n");

                $message = "Select a provider by replying with the number:\n{$providerList}";
                $this->apiServiceFactory->make($provider)->sendTextMessage($session->phone, $message);

                return;
            }
        }

        // 3) Fallback (modified)
        $this->apiServiceFactory->make($provider)->sendTextMessage(
            $session->phone,
            "I couldn't understand your message. Please reply with a valid keyword to continue."
        );
    }

    /**
     * After the user sent a number while in 'selecting_provider' state.
     */
    private function handleProviderSelection(WhatsappSession $session, string $text): void
    {
        $serviceTypeId = $session->context['service_type_id'] ?? null;

        if (! $serviceTypeId) {
            // Defensive reset; then re-route as normal text
            $session->context = [];
            $session->save();

            $fallbackProvider = Provider::first();
            if ($fallbackProvider) {
                $this->handleTextMessage($session, $fallbackProvider, $text);
            }

            return;
        }

        $providers = ServiceType::find($serviceTypeId)
            ?->providers()
            ->where('is_active', true)
            ->orderBy('name') // <-- Add this
            ->get() ?? collect();

        // User sees 1-based; convert to zero-based index
        $selectionIndex = max(0, ((int) trim($text)) - 1);

        if ($providers->has($selectionIndex)) {
            $selectedProvider = $providers->get($selectionIndex);
            $session->provider()->associate($selectedProvider)->save();

            $defaultFlow = $selectedProvider->flows()->first();
            if ($defaultFlow) {
                // clear selection state
                $session->context = [];
                $session->save();

                $this->startFlow($session, $defaultFlow);

                return;
            }

            $this->apiServiceFactory->make($selectedProvider)->sendTextMessage(
                $session->phone,
                'Sorry, this provider does not have an active flow.'
            );

            return;
        }

        // Invalid selection → re-prompt
        $providerList = $providers
            ->values()
            ->map(fn ($p, $i) => ($i + 1).". {$p->name}")
            ->implode("\n");

        $message = "Invalid selection. Please choose a provider by replying with their number:\n{$providerList}";
        $this->apiServiceFactory->make($session->provider)->sendTextMessage($session->phone, $message);
    }

    /**
     * Start a flow for a session at its first screen.
     */
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

    /**
     * Handle a Flow (NFM) reply.
     */
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

        // Build validation rules from screen components
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

        // Merge into context
        $ctx = (array) ($session->context ?? []);
        $session->update(['context' => array_merge($ctx, $responseData)]);

        // Resolve next screen
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

        // No more screens → end
        $this->endFlow($session, 'Thank you! We have received your information.');
    }

    /**
     * Render & send the current screen via Flow message.
     */
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
        $screenData = $this->flowRenderer->renderScreen(
            $screenConfig,
            (array) ($session->context ?? []),
            $errorMessage
        );

        $apiService->sendFlowMessage(
            $session->phone,
            $metaFlowId,
            (string) Str::uuid(),
            $screenData
        );
    }

    /**
     * Finish the flow, optionally sending a closing message.
     */
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

    /**
     * Decide the next screen to show based on the current screen config & reply data.
     *
     * Priority:
     *   1) next_on_choice map (if provided)
     *   2) explicit next_screen_id (if provided)
     *   3) next sequential screen in the flow
     */
    private function resolveNextScreenId(array $current, array $screens, array $responseData): ?string
    {
        // 1) Choice routing: map of "value" => "screen_id"
        $choiceMap = $current['data']['next_on_choice'] ?? null;
        if (is_array($choiceMap) && ! empty($responseData)) {
            foreach ($responseData as $value) {
                $val = is_array($value) ? ($value['value'] ?? null) : $value;
                if ($val !== null) {
                    $key = (string) $val;
                    if (array_key_exists($key, $choiceMap)) {
                        $target = (string) $choiceMap[$key];
                        if ($target !== '') {
                            return $target;
                        }
                    }
                }
            }
        }

        // 2) Explicit next screen
        if (! empty($current['data']['next_screen_id'])) {
            return (string) $current['data']['next_screen_id'];
        }

        // 3) Sequential fallback
        $ids = array_values(array_map(fn ($s) => $s['id'] ?? null, $screens));
        $idx = array_search($current['id'] ?? null, $ids, true);

        if ($idx !== false && isset($ids[$idx + 1])) {
            return (string) $ids[$idx + 1];
        }

        return null;
    }

    /**
     * Map builder component "type" => handler class.
     */
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

    /**
     * Re-render the same screen with a validation error.
     */
    private function sendValidationError(WhatsappSession $session, array $currentScreen, MessageBag $errors): void
    {
        $this->executeScreen($session, $currentScreen, $errors->first());
    }

    /**
     * Append a history event to the session.
     */
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
