<?php

namespace App\Flows\Components;

abstract class FlowComponent
{
    /** Full node JSON for this screen (incl. footer). */
    public array $node = [];

    /** Session context array (answers, vars, etc.). */
    public array $context = [];

    public function __construct(array $node, array $context = [])
    {
        $this->node = $node;
        $this->context = $context;
    }

    /**
     * Validate incoming user input for this component.
     * Return [bool $ok, mixed $normalizedValue, ?string $errorCode]
     */
    public function validate(mixed $input): array
    {
        // Default: accept any input
        return [true, $input, null];
    }

    /**
     * Decide the next screen from the node footer + input + context.
     * Supports:
     * - footer.next_on_choice[value] (for Dropdown/buttons)
     * - footer.next_on_ok
     * - footer.next_on_cancel (engine may use this for back/cancel)
     */
    public function resolveNext(mixed $input): ?string
    {
        $footer = $this->node['footer'] ?? [];

        // choice-based branching
        if (isset($footer['next_on_choice'])) {
            $key = is_array($input) ? ($input['value'] ?? null) : $input;
            if ($key !== null && array_key_exists($key, $footer['next_on_choice'])) {
                $target = $footer['next_on_choice'][$key];

                return $target !== '' ? $target : null;
            }
        }

        // default OK path
        if (! empty($footer['next_on_ok'])) {
            return $footer['next_on_ok'];
        }

        // cancel/back path (optional; your engine decides when to use)
        if (! empty($footer['next_on_cancel'])) {
            return $footer['next_on_cancel'];
        }

        return null; // no mapping → let engine fallback to sequential
    }

    /** Optional: side effects when entering this screen */
    public function onEnter(): void {}

    /** Optional: side effects when leaving this screen */
    public function onLeave(mixed $input): void {}
}
