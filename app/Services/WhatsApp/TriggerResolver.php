<?php

namespace App\Services\WhatsApp;

use App\Models\FlowTrigger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TriggerResolver
{
    public function resolve(?string $text, ?int $providerId = null): ?FlowTrigger
    {
        if (! $text) {
            return null;
        }

        // take first word only (ignore extra words)
        $first = Str::of($text)->trim()->explode(' ')->first();
        if (! $first) {
            return null;
        }

        Log::info('TriggerResolver: Searching for trigger.', [
            'keyword' => $first,
            'provider_id' => $providerId,
        ]);

        // The `when` clause is no longer needed, as the UsesTenantConnection trait on the
        // FlowTrigger model will automatically scope this query to the current provider.
        return FlowTrigger::query()
            ->whereRaw('LOWER(keyword) = ?', [mb_strtolower($first)])
            ->where('is_active', true)
            ->orderBy('priority')   // lowest number first
            ->orderByDesc('id')
            ->first();
    }
}
