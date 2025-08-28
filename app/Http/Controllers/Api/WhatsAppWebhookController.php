<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Services\WhatsAppMessageHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Netflie\WhatsAppCloudApi\WebHook;

class WhatsAppWebhookController extends Controller
{
    /**
     * WhatsApp Cloud API Webhook endpoint (GET = verification, POST = events)
     *
     * Route example:
     * Route::match(['GET','POST'], '/wa/webhook/{provider}', [WhatsAppWebhookController::class, 'handle']);
     */
    public function handle(Request $request, WhatsAppMessageHandler $messageHandler, Provider $provider)
    {
        $rid = (string) Str::uuid();
        $ctx = [
            'rid'           => $rid,
            'provider_id'   => $provider->id,
            'provider_slug' => $provider->slug,
            'method'        => $request->method(),
            'ip'            => $request->ip(),
        ];

        Log::info('WA webhook: request start', $ctx);

        try {
            $webhook = new WebHook();

            // === GET: Verification handshake (Meta calls this when you set the URL) ===
            if ($request->isMethod('get')) {
                $expectedToken = (string) data_get($provider->meta, 'verify_token', config('services.whatsapp.verify_token'));
                // The SDK returns the challenge string on success, or throws on mismatch
                $challenge = $webhook->verify($request->query->all(), $expectedToken);

                Log::info('WA webhook: verification OK (SDK)', $ctx);
                return response($challenge, 200);
            }

            // === POST: Incoming notifications ===
            if ($request->isMethod('post')) {
                // Optional but recommended: verify X-Hub-Signature-256 using your App Secret
                $appSecret = (string) data_get($provider->meta, 'app_secret', config('services.whatsapp.app_secret'));
                $sigHeader = (string) $request->header('X-Hub-Signature-256', '');

                if ($appSecret !== '' && $sigHeader !== '') {
                    $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);
                    if (! hash_equals($expected, $sigHeader)) {
                        Log::warning('WA webhook: signature verification FAILED', $ctx + [
                            'sig' => $this->maskHash($sigHeader),
                        ]);
                        return response('Invalid signature', 401);
                    }
                }

                // Use raw JSON payload for your existing handler
                $payload = $request->json()->all();
                $meta    = $this->extractMeta($payload);

                Log::info('WA webhook: POST received', $ctx + $meta);

                // If you prefer, you can parse via SDK:
                // $notifications = (new WebHook())->readAll($payload); // returns Notification[]
                // foreach ($notifications as $n) { /* transform & forward to your handler */ }

                $messageHandler->process($payload, $provider);

                Log::info('WA webhook: processed OK', $ctx);
                return response()->noContent(); // 204
            }
        } catch (\Throwable $e) {
            Log::error('WA webhook: handler error', $ctx + [
                'exception' => $e->getMessage(),
                'trace_top' => collect(explode("\n", $e->getTraceAsString()))->take(5)->implode("\n"),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
        }

        Log::warning('WA webhook: unsupported method', $ctx);
        return response('Unsupported method', 405);
    }

    /**
     * Mask a string, keeping head/tail visible.
     */
    private function mask(?string $value, int $head = 3, int $tail = 3): ?string
    {
        if ($value === null) return null;
        $len = strlen($value);
        if ($len <= $head + $tail) return str_repeat('*', $len);

        return substr($value, 0, $head)
            . str_repeat('*', $len - $head - $tail)
            . substr($value, -$tail);
    }

    /**
     * Mask long HMAC signatures like "sha256=abcd...".
     */
    private function maskHash(?string $sig, int $keep = 8): ?string
    {
        if ($sig === null || $sig === '') return $sig;
        if (! Str::startsWith($sig, 'sha256=')) return $this->mask($sig, 6, 6);

        $hex = substr($sig, 7);
        if (strlen($hex) <= $keep * 2) return 'sha256=' . $hex;

        return 'sha256=' . substr($hex, 0, $keep) . '…' . substr($hex, -$keep);
    }

    /**
     * Pull useful identifiers from the WA payload for logging.
     */
    private function extractMeta(array $p): array
    {
        $entry    = $p['entry'][0] ?? [];
        $change   = $entry['changes'][0] ?? [];
        $value    = $change['value'] ?? [];
        $messages = $value['messages'] ?? null;
        $statuses = $value['statuses'] ?? null;
        $meta     = $value['metadata'] ?? [];

        return [
            'waba_id'             => $entry['id'] ?? null,
            'phone_number_id'     => $meta['phone_number_id'] ?? null,
            'display_phone'       => $meta['display_phone_number'] ?? null,
            'has_messages'        => is_array($messages),
            'has_statuses'        => is_array($statuses),
            'message_types'       => $messages ? collect($messages)->pluck('type')->unique()->values()->all() : null,
            'status_samples'      => $statuses ? collect($statuses)->pluck('status')->unique()->values()->all() : null,
            'conversation_origin' => $statuses[0]['conversation']['origin']['type'] ?? null,
            'message_id_sample'   => $messages[0]['id'] ?? ($statuses[0]['id'] ?? null),
        ];
    }
}
