<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppMessageHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppWebhookController extends Controller
{
    public function handle(Request $request, WhatsAppMessageHandler $messageHandler, \App\Models\Provider $provider)
    {
        // Correlation id for this request (easy grepping)
        $rid = (string) Str::uuid();

        // Basic request context (don’t log raw body or secrets)
        $ctx = [
            'rid'           => $rid,
            'provider_id'   => $provider->id,
            'provider_slug' => $provider->slug,
            'method'        => $request->method(),
            'host'          => $request->getHost(),
            'path'          => $request->getPathInfo(),
            'ip'            => $request->ip(),
            'ua'            => $request->userAgent(),
        ];

        Log::info('WA webhook: request start', $ctx);

        // === GET: Verification ===
        if ($request->isMethod('get')) {
            $mode      = $this->param($request, ['hub_mode', 'hub.mode']);
            $token     = $this->param($request, ['hub_verify_token', 'hub.verify_token']);
            $challenge = $this->param($request, ['hub_challenge', 'hub.challenge']);

            $expectedToken = (string) data_get($provider->meta, 'verify_token', config('services.whatsapp.verify_token'));

            Log::info('WA webhook: GET verify received', $ctx + [
                'mode'          => $mode,
                'provided_token'=> $this->mask($token),
                'expected_token'=> $this->mask($expectedToken),
                'challenge_len' => $challenge !== null ? strlen((string) $challenge) : null,
            ]);

            if ($mode === 'subscribe' && hash_equals($expectedToken, (string) $token)) {
                Log::info('WA webhook: verification OK', $ctx);
                return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
            }

            Log::warning('WA webhook: verification FAILED (token mismatch)', $ctx);
            return response('Verification token mismatch', 403);
        }

        // === POST: Incoming events ===
        if ($request->isMethod('post')) {
            $signatureHeader = (string) $request->header('X-Hub-Signature-256'); // "sha256=..."
            $appSecret       = (string) data_get($provider->meta, 'app_secret', config('services.whatsapp.app_secret'));

            Log::info('WA webhook: POST received (pre-verify)', $ctx + [
                'sig_present' => $signatureHeader !== '',
                'sig_prefix'  => Str::startsWith($signatureHeader, 'sha256='),
                'app_secret_set' => $appSecret !== '',
            ]);

            if (! $signatureHeader || ! Str::startsWith($signatureHeader, 'sha256=') || $appSecret === '') {
                Log::warning('WA webhook: missing/invalid signature header or app secret', $ctx);
                return response('Invalid signature', 401);
            }

            $raw      = $request->getContent();
            $expected = 'sha256=' . hash_hmac('sha256', $raw, $appSecret);

            $sigMatch = hash_equals($expected, $signatureHeader);
            Log::info('WA webhook: signature check', $ctx + [
                'sig_match'   => $sigMatch,
                'sig_provided'=> $this->maskHash($signatureHeader),
                'sig_expected'=> $this->maskHash($expected),
            ]);

            if (! $sigMatch) {
                Log::warning('WA webhook: signature verification FAILED', $ctx);
                return response('Invalid signature', 401);
            }

            // Decode AFTER signature verification
            $payload = json_decode($raw, true) ?? [];
            $meta = $this->extractMeta($payload);

            Log::info('WA webhook: payload meta', $ctx + $meta);

            try {
                $messageHandler->process($payload, $provider);
            } catch (\Throwable $e) {
                // Explicitly dump and die for debugging this specific issue.
                dd($e);
            }

            Log::info('WA webhook: processed OK', $ctx);
            return response()->noContent(); // 204
        }

        Log::warning('WA webhook: unsupported method', $ctx);
        return response('Unsupported method', 405);
    }

    /** Safely read either underscore or dot query keys */
    private function param(Request $request, array $keys, $default = null)
    {
        foreach ($keys as $k) {
            $val = $request->query($k);
            if ($val !== null) return $val;
            $val = $request->input($k);
            if ($val !== null) return $val;
        }
        return $default;
    }

    /** Mask sensitive strings (show head/tail only) */
    private function mask(?string $value, int $head = 3, int $tail = 3): ?string
    {
        if ($value === null) return null;
        $len = strlen($value);
        if ($len <= $head + $tail) return str_repeat('*', $len);
        return substr($value, 0, $head) . str_repeat('*', $len - $head - $tail) . substr($value, -$tail);
    }

    /** Mask long hashes like "sha256=abcd..." → "sha256=abcd…(8)…WXYZ" */
    private function maskHash(?string $sig, int $keep = 8): ?string
    {
        if ($sig === null || $sig === '') return $sig;
        if (!Str::startsWith($sig, 'sha256=')) return $this->mask($sig, 6, 6);
        $hex = substr($sig, 7);
        if (strlen($hex) <= $keep * 2) return 'sha256=' . $hex;
        return 'sha256=' . substr($hex, 0, $keep) . '…' . substr($hex, -$keep);
    }

    /** Pull useful identifiers from the WA payload to log */
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
