<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppMessageHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppWebhookController extends Controller
{
    public function handle(Request $request, WhatsAppMessageHandler $messageHandler)
    {
        // --- Logic for Webhook Verification (GET request) ---
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
            $signature = $request->header('X-Hub-Signature-256');

            if (! $signature) {
                abort(404);
            }

            $hash = hash_hmac(
                'sha256',
                $request->getContent(),
                config('services.whatsapp.app_secret')
            );

            if (! hash_equals($hash, Str::after($signature, 'sha256='))) {
                abort(403, 'Signature verification failed.');
            }

            try {
                $messageHandler->process($request->all());
            } catch (\Throwable $e) {
                Log::error('Error processing webhook', ['exception' => $e]);

                return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
            }

            return response()->json(['status' => 'success'], 200);
        }

        return response('Unsupported method', 405);
    }
}
