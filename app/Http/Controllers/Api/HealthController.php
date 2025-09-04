<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class HealthController extends Controller
{
    public function __invoke()
    {
        return response()->json(['ok' => true, 'time' => now()->toIso8601String()]);
    }
}
