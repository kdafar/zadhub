<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppMessageHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function handle(Request $request, WhatsAppMessageHandler $messageHandler)
    {
        // --- Logic for Webhook Verification (GET request) ---
        // Using the ->query() method as you confirmed it works reliably.
        if ($request->isMethod('get') && $request->query('hub_mode') === 'subscribe' && $request->query('hub_verify_token')) {

            $verifyToken = config('services.whatsapp.verify_token');

            if ($request->query('hub_verify_token') === $verifyToken) {
                Log::info('Webhook verification successful.');

                return response($request->query('hub_challenge'), 200);
            } else {
                Log::error('Webhook verification failed: Token mismatch.');

                return response('Verification token mismatch', 403);
            }
        }

        // --- Logic for Incoming Messages (POST request) ---
        if ($request->isMethod('post')) {
            // This is now active and will process incoming messages.
            $messageHandler->process($request->all());

            return response()->json(['status' => 'success'], 200);
        }

        return response('Unsupported method', 405);
    }
}
