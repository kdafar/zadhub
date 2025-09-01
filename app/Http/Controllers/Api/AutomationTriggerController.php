<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAutomationJob;
use App\Models\Automation;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AutomationTriggerController extends Controller
{
    public function handle(Request $request)
    {
        // Basic validation for now
        $validated = $request->validate([
            'provider_slug' => 'required|string|exists:providers,slug',
            'trigger_event' => 'required|string',
            'recipient_phone' => 'required|string',
            'data' => 'present|array',
        ]);

        $provider = Provider::where('slug', $validated['provider_slug'])->firstOrFail();

        $automation = Automation::where('provider_id', $provider->id)
            ->where('trigger_event', $validated['trigger_event'])
            ->where('is_active', true)
            ->first();

        if (! $automation) {
            Log::info('No active automation found for trigger.', $validated);

            return response()->json(['message' => 'No active automation found for this trigger.'], 404);
        }

        ProcessAutomationJob::dispatch(
            $automation->id,
            $validated['recipient_phone'],
            $validated['data']
        );

        Log::info('Automation triggered, dispatching job.', ['automation_id' => $automation->id]);

        return response()->json(['message' => 'Automation triggered successfully.']);
    }
}
