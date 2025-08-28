<?php

namespace App\Services;

use App\Models\Provider;
use Illuminate\Support\Facades\Log;

class WhatsAppApiServiceFactory
{
    public function make(?Provider $provider = null): WhatsAppApiService|WhatsAppApiServiceFake
    {
        $useFake = config('services.whatsapp.fake', app()->environment('local'));

        if ($useFake) {
            return new WhatsAppApiServiceFake;
        }

        $token = $provider?->api_token;
        $phoneId = $provider?->whatsapp_phone_number_id;

        if (empty($token) || empty($phoneId)) {
            Log::critical('WhatsApp API credentials missing for provider.', [
                'provider_id' => $provider?->id,
                'has_token' => ! empty($token),
                'has_phone_id' => ! empty($phoneId),
            ]);

            throw new \Exception('WhatsApp API credentials are not configured for provider ID: '.$provider?->id);
        }

        return new \App\Services\WhatsAppApiService($token, $phoneId);
    }
}
