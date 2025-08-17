<?php

namespace App\Flows\Components;

class TextInput extends FlowComponent
{
    /**
     * Node may include:
     *  - rules.required (bool)
     *  - rules.min (int chars)
     *  - rules.max (int chars)
     *  - rules.regex (PCRE string)
     */
    public function validate(mixed $input): array
    {
        $rules = $this->node['rules'] ?? [];
        $value = is_string($input) ? trim($input) : (string) $input;

        if (($rules['required'] ?? false) && ($value === '')) {
            return [false, null, 'required'];
        }

        if (isset($rules['min']) && mb_strlen($value) < (int) $rules['min']) {
            return [false, null, 'min'];
        }

        if (isset($rules['max']) && mb_strlen($value) > (int) $rules['max']) {
            return [false, null, 'max'];
        }

        if (! empty($rules['regex']) && ! @preg_match($rules['regex'], $value)) {
            // invalid pattern → ignore rule instead of breaking
        } elseif (! empty($rules['regex']) && ! preg_match($rules['regex'], $value)) {
            return [false, null, 'format'];
        }

        return [true, $value, null];
    }
}
