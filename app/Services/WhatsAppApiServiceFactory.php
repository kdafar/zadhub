<?php

namespace App\Services;

use App\Models\Provider;

class WhatsAppApiServiceFactory
{
    public function make(?Provider $provider = null): WhatsAppApiService|WhatsAppApiServiceFake
    {
        $useFake = config('services.whatsapp.fake', app()->environment('local'));

        if ($useFake) {
            return new WhatsAppApiServiceFake;
        }

        if (! $provider) {
            $token = config('services.whatsapp.api_token');
            $phoneId = config('services.whatsapp.phone_number_id');
        } else {
            $token = $provider->api_token;
            $phoneId = $provider->whatsapp_phone_number_id;
        }

        if (empty($token) || empty($phoneId)) {
            throw new \Exception('WhatsApp API credentials are not configured.');
        }

        return new WhatsAppApiService($token, $phoneId);
    }
}
