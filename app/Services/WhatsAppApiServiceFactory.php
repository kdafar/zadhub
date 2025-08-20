<?php

namespace App\Services;

use App\Models\Provider;

class WhatsAppApiServiceFactory
{
    public function make(): WhatsAppApiService|WhatsAppApiServiceFake
    {
        $useFake = config('services.whatsapp.fake', app()->environment('local'));

        if ($useFake) {
            return new WhatsAppApiServiceFake();
        }

        return new WhatsAppApiService(
            config('services.whatsapp.api_token'),
            config('services.whatsapp.phone_number_id')
        );
    }
}
