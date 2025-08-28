<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppMessageHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Netflie\WhatsAppCloudApi\Webhooks\Webhook;

class WhatsAppWebhookController extends Controller
{
    public function handle(Request $request, WhatsAppMessageHandler $messageHandler, \App\Models\Provider $provider)
    {
        $rid = (string) \Illuminate\Support\Str::uuid();
        $ctx = [
            'rid' => $rid,
            'provider_id' => $provider->id,
            'provider_slug' => $provider->slug,
        ];

        try {
            $webhook = new Webhook();

            if (! $webhook->verify($request, (string) data_get($provider->meta, 'verify_token'))) {
                Log::warning('WA webhook: verification FAILED (token mismatch)', $ctx);

                return response('Verification token mismatch', 403);
            }

            if ($request->isMethod('get')) {
                Log::info('WA webhook: verification OK', $ctx);

                return response($request->query('hub_challenge'), 200)->header('Content-Type', 'text/plain');
            }

            if (! $webhook->verifyHmac($request, (string) data_get($provider->meta, 'app_secret'))) {
                Log::warning('WA webhook: signature verification FAILED', $ctx);

                return response('Invalid signature', 401);
            }

            $payload = $request->all();
            Log::info('WA webhook: POST received and verified', $ctx);

            $messageHandler->process($payload, $provider);

            return response()->noContent(); // 204
        } catch (\Throwable $e) {
            Log::error('WA webhook: handler error', $ctx + [
                'exception' => $e->getMessage(),
                'trace_top' => collect(explode("\n", $e->getTraceAsString()))->take(5)->implode("\n"),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
        }
    }
}
