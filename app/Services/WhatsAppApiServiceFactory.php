<?php

namespace App\Services;

use App\Models\Provider;

class WhatsAppApiServiceFactory
{
    public function make(Provider $provider): WhatsAppApiService|WhatsAppApiServiceFake
    {
        $useFake = config('services.whatsapp.fake', app()->environment('local'));

        if ($useFake) {
            return new WhatsAppApiServiceFake();
        }

        return new WhatsAppApiService($provider->api_token ?? '', $provider->whatsapp_phone_number_id ?? '');
    }
}
