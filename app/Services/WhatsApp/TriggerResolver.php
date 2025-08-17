<?php

namespace App\Services\WhatsApp;

use App\Models\FlowTrigger;
use Illuminate\Support\Str;

class TriggerResolver
{
    public function resolve(?string $text): ?FlowTrigger
    {
        if (! $text) {
            return null;
        }

        // take first word only (ignore extra words)
        $first = Str::of($text)->trim()->explode(' ')->first();
        if (! $first) {
            return null;
        }

        // match case-insensitively
        return FlowTrigger::query()
            ->whereRaw('LOWER(keyword) = ?', [mb_strtolower($first)])
            ->where('is_active', true)
            ->orderBy('priority')   // lowest number first
            ->orderByDesc('id')
            ->first();
    }
}
