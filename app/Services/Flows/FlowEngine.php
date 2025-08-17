<?php

namespace App\Services\Flows;

use Illuminate\Support\Arr;

class FlowEngine
{
    public static function getScreens(array $flowDef): array
    {
        return $flowDef['screens'] ?? [];
    }

    public static function getStartScreenId(array $flowDef): ?string
    {
        return Arr::get($flowDef, 'meta.start')
            ?? (self::getScreens($flowDef)[0]['id'] ?? null);
    }

    public static function getScreenById(array $flowDef, ?string $screenId): ?array
    {
        if (! $screenId) {
            return null;
        }
        foreach (self::getScreens($flowDef) as $s) {
            if (($s['id'] ?? null) === $screenId) {
                return $s;
            }
        }

        return null;
    }

    /**
     * Branching order:
     * 1) Component-level routing (e.g., Dropdown option.next, any component next if value present)
     * 2) footer.routes[] rules (minimal ctx equality: "ctx.locale==ar")
     * 3) footer.next fallback
     * 4) Linear fallback = next screen in the list
     */
    public static function determineNextScreenId(array $flowDef, array $currentScreen, array $input, array $ctx = []): ?string
    {
        // 1) components
        $components = $currentScreen['components'] ?? [];
        foreach ($components as $comp) {
            $type = $comp['type'] ?? null;
            $name = $comp['name'] ?? null;
            $value = $name ? Arr::get($input, $name) : null;

            // Dropdown: options[].next
            if ($type === 'Dropdown' && isset($comp['options']) && $value !== null) {
                foreach ($comp['options'] as $opt) {
                    if (($opt['value'] ?? null) == $value && ! empty($opt['next'])) {
                        return (string) $opt['next'];
                    }
                }
            }

            // Any component can declare "next" when it receives a value
            if (! empty($comp['next']) && $value !== null && $value !== '') {
                return (string) $comp['next'];
            }
        }

        // 2) footer.routes
        $footer = $currentScreen['footer'] ?? [];
        if (! empty($footer['routes']) && is_array($footer['routes'])) {
            foreach ($footer['routes'] as $rule) {
                $expr = $rule['if'] ?? '';
                if (self::evalCtxEqualsExpr($expr, $ctx)) {
                    return $rule['next'] ?? null;
                }
            }
        }

        // 3) footer.next
        if (! empty($footer['next'])) {
            return (string) $footer['next'];
        }

        // 4) Linear fallback
        $screens = self::getScreens($flowDef);
        $currentId = $currentScreen['id'] ?? null;
        foreach ($screens as $i => $s) {
            if (($s['id'] ?? null) === $currentId) {
                return $screens[$i + 1]['id'] ?? null;
            }
        }

        return null;
    }

    /**
     * Minimal evaluator for "ctx.key==value" (no quotes).
     * Example: ctx.locale==ar
     */
    protected static function evalCtxEqualsExpr(string $expr, array $ctx): bool
    {
        if (! $expr) {
            return false;
        }
        if (preg_match('/^ctx\\.([a-zA-Z0-9_]+)\\s*==\\s*([\\w-]+)$/', $expr, $m)) {
            $key = $m[1];
            $val = $m[2];

            return (string) ($ctx[$key] ?? '') === (string) $val;
        }

        return false;
    }
}
