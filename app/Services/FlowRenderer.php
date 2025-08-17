<?php

namespace App\Services;

use App\Flows\Components\DatePicker;
use App\Flows\Components\Dropdown;
// Components
use App\Flows\Components\FlowComponent;
use App\Flows\Components\Image;
use App\Flows\Components\TextBody;
use App\Flows\Components\TextInput;
use App\Models\FlowVersion;
use App\Models\WhatsappSession;

// WA transport
// adjust the namespace if yours differs

class FlowRenderer
{
    protected ?WhatsAppApiService $wa = null;

    public function __construct(?WhatsAppApiService $wa = null)
    {
        $this->wa = $wa;
    }

    /**
     * Build a component instance for a given node with session context.
     */
    public function makeComponent(array $node, array $context = []): FlowComponent
    {
        $type = $node['type'] ?? 'TextBody';

        return match ($type) {
            'TextInput' => new TextInput($node, $context),
            'Dropdown' => new Dropdown($node, $context),
            'Image' => new Image($node, $context),
            'DatePicker' => new DatePicker($node, $context),
            default => new TextBody($node, $context),
        };
    }

    /**
     * Get the flow graph (id => node) for a FlowVersion.
     * We try common field names and normalize to an associative array keyed by node id.
     */
    public function getGraph(int $flowVersionId): array
    {
        /** @var FlowVersion|null $version */
        $version = FlowVersion::query()->find($flowVersionId);
        if (! $version) {
            return [];
        }

        // Try a few likely properties that may contain the JSON graph.
        $raw = $version->graph
            ?? $version->definition
            ?? $version->content
            ?? $version->payload
            ?? null;

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
        } elseif (is_array($raw)) {
            $decoded = $raw;
        } else {
            $decoded = null;
        }

        // Expected structures we normalize:
        // 1) ['nodes' => [ {id: 'A', ...}, ... ]]
        // 2) [ {id: 'A', ...}, ... ]
        // 3) ['id' => ['node data'], ...] already keyed
        $graph = [];

        if (is_array($decoded)) {
            if (isset($decoded['nodes']) && is_array($decoded['nodes'])) {
                foreach ($decoded['nodes'] as $node) {
                    if (! empty($node['id'])) {
                        $graph[$node['id']] = $node;
                    }
                }
            } else {
                // Either keyed or list
                $isList = array_is_list($decoded);
                if ($isList) {
                    foreach ($decoded as $node) {
                        if (is_array($node) && ! empty($node['id'])) {
                            $graph[$node['id']] = $node;
                        }
                    }
                } else {
                    // Already keyed by id?
                    $allHaveIdKeys = true;
                    foreach ($decoded as $k => $node) {
                        if (! is_array($node)) {
                            $allHaveIdKeys = false;
                            break;
                        }
                    }
                    $graph = $allHaveIdKeys ? $decoded : [];
                }
            }
        }

        return $graph;
    }

    /**
     * Render the given node for a session (send the appropriate WhatsApp message).
     * Minimal assumptions about WhatsAppApiService:
     *  - sendText(string $to, string $text)
     *  - sendImage(string $to, string $mediaUrlOrId, ?string $caption = null)
     *  - sendList(string $to, string $header, string $body, array $rows, ?string $footer = null)
     * Adjust method names if your service differs.
     */
    public function renderScreen(WhatsappSession $session, array $node): void
    {
        $type = $node['type'] ?? 'TextBody';
        $phone = $session->phone;
        $locale = $session->locale ?? 'en';

        switch ($type) {
            case 'TextBody':
                $text = $this->fmtText($node, $locale);
                if ($text !== '') {
                    $this->wa->sendText($phone, $text);
                }
                break;

            case 'Image':
                $url = (string) ($node['url'] ?? $node['media'] ?? '');
                $caption = $this->fmtText($node, $locale);
                if ($url !== '') {
                    $this->wa->sendImage($phone, $url, $caption ?: null);
                } else {
                    // Fallback to text if image missing
                    $this->wa->sendText($phone, $caption !== '' ? $caption : ' ');
                }
                break;

            case 'Dropdown':
                // Render as a WhatsApp List (header + rows)
                $header = (string) ($node['title'] ?? $node['header'] ?? 'Select');
                $body = $this->fmtText($node, $locale) ?: 'Choose an option';
                $footer = (string) ($node['footer']['hint'] ?? '');
                $rows = $this->mapDropdownToListRows($node);

                // If your WA service uses different signature, adjust here.
                $this->wa->sendList($phone, $header, $body, $rows, $footer ?: null);
                break;

            case 'TextInput':
                // Prompt user with text. Validation happens when input received.
                $prompt = $this->fmtText($node, $locale) ?: 'Please type your answer.';
                $this->wa->sendText($phone, $prompt);
                break;

            case 'DatePicker':
                // Simple prompt. Your handler should parse dates and re‑ask if invalid.
                $prompt = $this->fmtText($node, $locale) ?: 'Please send a date (YYYY-MM-DD).';
                $this->wa->sendText($phone, $prompt);
                break;

            default:
                // Unknown type → fallback to text
                $text = $this->fmtText($node, $locale) ?: ' ';
                $this->wa->sendText($phone, $text);
                break;

        }
    }

    /**
     * Convenience: render the current screen of a session from its flow_version_id.
     */
    public function renderCurrent(WhatsappSession $session): void
    {
        $graph = $this->getGraph((int) $session->flow_version_id);
        if (empty($graph)) {
            $this->wa->sendText($session->phone, 'Flow not available.');

            return;
        }

        $currentId = $session->current_screen ?: array_key_first($graph);
        $node = $graph[$currentId] ?? null;

        if (! $node) {
            $this->wa->sendText($session->phone, 'Screen not found.');

            return;
        }

        $this->renderScreen($session, $node);
    }

    /**
     * Format a node’s text/body with a basic fallback.
     */
    protected function fmtText(array $node, string $locale = 'en'): string
    {
        // Prefer localized text if provided
        if (! empty($node['text_localized'][$locale])) {
            return (string) $node['text_localized'][$locale];
        }

        // Otherwise generic text or title/body combos
        if (! empty($node['text'])) {
            return (string) $node['text'];
        }

        $title = (string) ($node['title'] ?? '');
        $body = (string) ($node['body'] ?? '');
        $out = trim($title.($title && $body ? "\n\n" : '').$body);

        return $out;
    }

    /**
     * Convert Dropdown node options to WhatsApp List rows.
     * Expected node.options: [{label, value, description?}, ...]
     * Return format (example): [['id' => 'value', 'title' => 'label', 'description' => '...'], ...]
     */
    protected function mapDropdownToListRows(array $node): array
    {
        $rows = [];
        foreach (($node['options'] ?? []) as $opt) {
            $value = (string) ($opt['value'] ?? '');
            $label = (string) ($opt['label'] ?? $value);
            if ($value === '') {
                continue;
            }

            $rows[] = [
                'id' => $value,
                'title' => $label,
                'description' => (string) ($opt['description'] ?? ''),
            ];
        }

        // Some WA APIs need sections. If your WhatsAppApiService expects sections,
        // you can wrap as:
        // return [[ 'title' => $node['section_title'] ?? 'Options', 'rows' => $rows ]];
        return $rows;
    }
}
