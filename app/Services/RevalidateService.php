<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RevalidateService
{
    public function trigger(array $paths): bool
    {
        $url = rtrim(env('NEXT_REVALIDATE_URL', ''), '/');
        $secret = env('NEXT_REVALIDATE_SECRET', '');
        if (! $url || ! $secret || empty($paths)) {
            return false;
        }

        $res = Http::asJson()->timeout(8)->post($url, ['secret' => $secret, 'paths' => $paths]);
        if (! $res->successful()) {
            Log::warning('Revalidate failed', ['status' => $res->status(), 'body' => $res->body()]);

            return false;
        }

        return true;
    }
}
