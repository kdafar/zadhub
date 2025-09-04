<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class LeadController extends Controller
{
    public function store(Request $request)
    {
        $perMin = (int) env('LEADS_RATE_PER_MIN', 5);
        $perDay = (int) env('LEADS_RATE_PER_DAY', 50);
        $ip = $request->ip() ?? '0.0.0.0';

        if (! RateLimiter::attempt('leads:min:'.$ip, $perMin, fn () => null, 60)) {
            return $this->tooMany('Too many submissions, try again in a minute.');
        }
        if (! RateLimiter::attempt('leads:day:'.$ip, $perDay, fn () => null, 24 * 60)) {
            return $this->tooMany('Daily submission limit reached.');
        }

        // Normalize before validating
        $request->merge([
            'use_case' => strtolower((string) $request->input('use_case', '')),
            'phone' => preg_replace('/\s+/', '', (string) $request->input('phone', '')),
        ]);

        $rules = [
            'name' => ['nullable', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            // Kuwait + GCC (adjust as needed)
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+(?:965\d{8}|966\d{9}|971\d{9}|974\d{8}|973\d{8}|968\d{8}|20\d{10})$/'],
            'use_case' => ['nullable', 'in:restaurant,pharmacy,grocery,logistics,other'],
            'locale' => ['required', 'in:en,ar'],
            'message' => ['nullable', 'string', 'max:500'],
            'utm' => ['nullable', 'array'],
        ];

        $v = Validator::make($request->all(), $rules);
        if ($v->fails()) {
            return response()->json([
                'ok' => false,
                'code' => 'VALIDATION_ERROR',
                'message' => 'Validation failed',
                'errors' => $v->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $lead = LandingLead::create([
            'name' => (string) $request->input('name', ''),
            'company' => (string) $request->input('company', ''),
            'email' => (string) $request->input('email', ''),
            'phone' => (string) $request->input('phone', ''),
            'use_case' => (string) $request->input('use_case', ''),
            'locale' => (string) $request->input('locale', 'en'),
            'message' => (string) $request->input('message', ''),
            'utm' => $request->input('utm') ?: null,
            'ip' => $ip,
        ]);

        // TODO: dispatch WhatsApp thank-you job, email/slack notify, etc.

        return response()->json(['ok' => true, 'id' => $lead->id], 200);
    }
}
