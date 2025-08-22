<?php

namespace App\Services\Flows;

use App\Models\WhatsappSession;
use Illuminate\Support\Facades\Log;

class FlowActionService
{
    /**
     * Execute a list of actions defined for a screen.
     *
     * @param  array  $actions  The array of action configurations.
     * @param  WhatsappSession  $session  The current user session.
     * @return string|null The ID of the next screen to transition to, or null to continue normal flow.
     */
    public function executeActions(array $actions, WhatsappSession $session): ?string
    {
        Log::info('Executing actions for session', ['session_id' => $session->id, 'action_count' => count($actions)]);

        $nextScreenId = null;

        foreach ($actions as $action) {
            $result = match ($action['type'] ?? null) {
                'api_call' => $this->handleApiCall($action, $session),
                default => null,
            };

            // The last action that returns a screen ID wins
            if ($result) {
                $nextScreenId = $result;
            }
        }

        return $nextScreenId;
    }

    private function handleApiCall(array $action, WhatsappSession $session): ?string
    {
        Log::info('Handling api_call action', ['session_id' => $session->id, 'config' => $action]);

        // TODO: Implement the actual HTTP request using the provider's credentials.
        // For now, we'll simulate a successful call and merge dummy data.

        $context = $session->context ?? [];
        $context['api_data'] = ['success' => true, 'message' => 'Called API successfully!'];
        $session->update(['context' => $context]);

        // Return the screen to go to on success
        return $action['on_success'] ?? null;
    }
}
