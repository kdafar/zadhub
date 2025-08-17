<?php

namespace App\Flows\Components;

class Dropdown extends FlowComponent
{
    /**
     * Expects node.options = [{label, value}, ...]
     * Accepts either the raw value (string/int) or { value: ... } shape.
     */
    public function validate(mixed $input): array
    {
        $options = $this->node['options'] ?? [];
        $allowed = array_map(fn ($o) => $o['value'] ?? null, $options);
        $allowed = array_values(array_filter($allowed, fn ($v) => $v !== null));

        $value = is_array($input) ? ($input['value'] ?? null) : $input;

        if (! in_array($value, $allowed, true)) {
            return [false, null, 'invalid_choice'];
        }

        return [true, $value, null];
    }
}
