<?php

namespace App\Services\WhatsApp;

use App\Models\FlowVersion;
use App\Models\WhatsappSession;
use App\Services\Flows\FlowEngine;
use App\Services\Flows\FlowRenderer;
use DomainException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WhatsAppMessageHandler
{
    public function __construct(
        protected WhatsAppApiService $whatsapp,
        protected FlowRenderer $renderer,
        protected TriggerResolver $triggers
    ) {}

    /**
     * Entry point you call from your webhook controller.
     * $payload = raw webhook array; $phone = E164 string.
     */
    public function handle(array $payload, string $phone): void
    {
        $session = $this->getOrCreateSession($phone);

        try {
            $this->processIncomingForSession($session, $payload);
        } catch (DomainException $e) {
            // validation / user-friendly errors: re-render same screen
            Log::warning('[Flow Validation]', ['phone' => $phone, 'msg' => $e->getMessage()]);
            $this->whatsapp->sendText($phone, $e->getMessage());
            $this->renderCurrent($session);
        } catch (\Throwable $e) {
            // developer errors
            Log::error('Flow error', ['phone' => $phone, 'err' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->whatsapp->sendText($phone, __('An unexpected error occurred. Please try again.'));
        }
    }

    protected function getOrCreateSession(string $phone): WhatsappSession
    {
        /** @var WhatsappSession $s */
        $s = WhatsappSession::firstOrCreate(
            ['phone' => $phone],
            [
                'status' => 'active',
                'locale' => 'en',
                'context' => [],
                'last_interacted_at' => now(),
            ]
        );

        // Assign a flow if none is set (your logic may differ: provider mapping, trigger keywords, etc.)
        if (! $s->flow_version_id) {
            $fv = FlowVersion::query()->latest('id')->first(); // choose a default for now
            if ($fv) {
                $s->flow_version_id = $fv->id;
                $s->current_screen = null; // start from meta.start
                $s->save();
            }
        }

        return $s;
    }

    /**
     * Core branching logic:
     * - Load flow definition
     * - Determine current screen (or start)
     * - Extract input from webhook according to current screen components
     * - Validate
     * - Merge context
     * - Determine next screen id
     * - Save + render next (or end)
     */
    protected function processIncomingForSession(WhatsappSession $session, array $payload): void
    {
        $incomingType = $this->detectIncomingType($payload);
        $userText = data_get($payload, 'messages.0.text.body');

        // If the session has no flow yet, try keyword routing
        if (! $session->flow_version_id) {
            $match = $this->triggers->resolve($userText);
            if ($match) {
                $session->service_id = $match->service_id;
                $session->provider_id = $match->provider_id;
                $session->flow_version_id = $match->flow_version_id;
                if ($match->locale) {
                    $session->locale = $match->locale;
                }
                $session->current_screen = null; // ensure start
                $session->appendHistory([
                    'event' => 'trigger_matched',
                    'meta' => [
                        'keyword' => $match->keyword,
                        'service_id' => $match->service_id,
                        'provider_id' => $match->provider_id,
                        'flow_version_id' => $match->flow_version_id,
                    ],
                ]);
                $session->save();
            } else {
                // No keyword matched — send a dynamic menu of available triggers
                $this->sendTriggerMenu($session->phone);

                return;
            }
        }

        $def = $this->getFlowDef($session->flow_version_id);

        $currentId = $session->current_screen ?: FlowEngine::getStartScreenId($def);
        $current = FlowEngine::getScreenById($def, $currentId);

        // If brand-new session, just render start and return (no input to process yet)
        $incomingType = $this->detectIncomingType($payload);
        if (! $session->current_screen && $incomingType !== 'postback') {
            $session->last_message_type = $incomingType;
            $session->last_payload = $payload;
            $session->last_interacted_at = now();
            $session->appendHistory(['event' => 'session_started', 'screen' => $currentId]);
            $session->save();

            $messages = $this->renderer->renderScreen($def, $currentId, $session->context ?? []);
            $this->sendMany($session->phone, $messages);
            $session->current_screen = $currentId;
            $session->save();

            return;
        }

        // Extract input for the current screen (based on its components)
        $input = $this->extractInputFromWebhook($payload, $current);

        // Validate against per-component rules
        $this->validateComponents($current['components'] ?? [], $input);

        // Merge into context
        $ctx = $session->context ?? [];
        foreach ($input as $k => $v) {
            $ctx[$k] = $v;
        }
        $session->context = $ctx;

        // Determine next screen
        $nextId = FlowEngine::determineNextScreenId($def, $current, $input, $ctx);

        // Persist + history
        $prev = $session->current_screen ?: $currentId;
        $session->current_screen = $nextId;
        $session->last_message_type = $incomingType;
        $session->last_payload = $payload;
        $session->last_interacted_at = now();
        $session->appendHistory([
            'event' => 'screen_changed',
            'screen' => $nextId,
            'meta' => ['from' => $prev, 'input' => $input],
        ]);
        $session->save();

        // End if nothing next
        if (! $nextId) {
            $session->status = 'ended';
            $session->ended_at = now();
            $session->ended_reason = 'flow_completed';
            $session->appendHistory(['event' => 'session_ended', 'meta' => ['reason' => 'flow_completed']]);
            $session->save();

            $this->whatsapp->sendText($session->phone, __('Thanks! We’re done.'));

            return;
        }

        // Render next
        $messages = $this->renderer->renderScreen($def, $nextId, $ctx);
        $this->sendMany($session->phone, $messages);
    }

    protected function getFlowDef(int $flowVersionId): array
    {
        $fv = FlowVersion::findOrFail($flowVersionId);
        $def = $fv->definition;
        if (is_string($def)) {
            $def = json_decode($def, true);
        }
        if (! is_array($def)) {
            throw new RuntimeException('Invalid flow definition JSON.');
        }

        return $def;
    }

    protected function detectIncomingType(array $payload): string
    {
        // Very small detector; adapt to your webhook
        if (isset($payload['messages'][0]['interactive'])) {
            return 'interactive';
        }
        if (isset($payload['messages'][0]['text'])) {
            return 'text';
        }
        if (isset($payload['postback'])) {
            return 'postback';
        }

        return 'unknown';
    }

    /**
     * Map WhatsApp webhook payload → { componentName => value } for the CURRENT screen.
     * Supports:
     *  - Text input for TextInput component
     *  - Interactive LIST selections for Dropdown component
     */
    protected function extractInputFromWebhook(array $payload, ?array $currentScreen): array
    {
        $out = [];
        $components = $currentScreen['components'] ?? [];

        // Input value from WhatsApp:
        $text = data_get($payload, 'messages.0.text.body');
        $interactiveType = data_get($payload, 'messages.0.interactive.type');
        $listId = data_get($payload, 'messages.0.interactive.list_reply.id');

        foreach ($components as $c) {
            $type = $c['type'] ?? null;
            $name = $c['name'] ?? null;
            if (! $name) {
                continue;
            }

            if ($type === 'TextInput' && $text !== null && $text !== '') {
                $out[$name] = $text;
            } elseif ($type === 'Dropdown' && $interactiveType === 'list_reply' && $listId !== null) {
                $out[$name] = $listId;
            }
            // DatePicker can accept text too:
            elseif ($type === 'DatePicker' && $text !== null && $text !== '') {
                $out[$name] = $text; // optional: parse/validate format
            }
        }

        return $out;
    }

    protected function validateComponents(array $components, array $input): void
    {
        foreach ($components as $c) {
            $name = $c['name'] ?? null;
            if (! $name) {
                continue;
            }

            $value = $input[$name] ?? null;

            if (! empty($c['required']) && ($value === null || $value === '')) {
                $label = $c['label'] ?? $name;
                throw new DomainException("{$label} is required.");
            }

            $rules = $c['validate'] ?? [];
            if (! empty($rules['min']) && mb_strlen((string) $value) < (int) $rules['min']) {
                throw new DomainException(($c['label'] ?? $name).' too short.');
            }
            if (! empty($rules['max']) && mb_strlen((string) $value) > (int) $rules['max']) {
                throw new DomainException(($c['label'] ?? $name).' too long.');
            }
            if (! empty($rules['regex']) && @preg_match('/'.$rules['regex'].'/u', '') !== false) {
                if (! preg_match('/'.$rules['regex'].'/u', (string) $value)) {
                    throw new DomainException(($c['label'] ?? $name).' invalid.');
                }
            }
        }
    }

    protected function renderCurrent(WhatsappSession $session): void
    {
        $def = $this->getFlowDef($session->flow_version_id);
        $screenId = $session->current_screen ?: FlowEngine::getStartScreenId($def);
        $messages = $this->renderer->renderScreen($def, $screenId, $session->context ?? []);
        $this->sendMany($session->phone, $messages);
    }

    protected function sendMany(string $phone, array $messages): void
    {
        foreach ($messages as $m) {
            switch ($m['type']) {
                case 'text':
                    $this->whatsapp->sendText($phone, $m['text']);
                    break;
                case 'image':
                    $this->whatsapp->sendImage($phone, $m['url'], $m['caption'] ?? null);
                    break;
                case 'list':
                    $this->whatsapp->sendList($phone, $m['title'], $m['buttonText'], $m['items']);
                    break;
                default:
                    // ignore unknown
                    break;
            }
        }
    }

    protected function sendTriggerMenu(string $phone): void
    {
        $active = \App\Models\FlowTrigger::query()
            ->where('is_active', true)
            ->orderBy('priority')
            ->get(['keyword', 'service_id', 'provider_id']);

        if ($active->isEmpty()) {
            $this->whatsapp->sendText($phone, 'No services available yet. Please try again later.');

            return;
        }

        // Group by service/provider (basic display)
        $lines = ['Available services:'];
        foreach ($active as $t) {
            $label = $t->keyword;
            if ($t->service_id || $t->provider_id) {
                $svc = $t->service_id ? optional(\App\Models\Service::find($t->service_id))->name : null;
                $pro = $t->provider_id ? optional(\App\Models\Provider::find($t->provider_id))->name : null;
                $suffix = trim(collect([$svc, $pro])->filter()->implode(' • '));
                if ($suffix !== '') {
                    $label .= " — {$suffix}";
                }
            }
            $lines[] = '• '.$label;
        }

        $this->whatsapp->sendText($phone, implode("\n", $lines)."\n\nType a keyword to begin (e.g.,: ".$active->first()->keyword.')');
    }
}
