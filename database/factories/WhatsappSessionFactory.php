<?php

namespace Database\Factories;

use App\Models\Provider;
use App\Models\WhatsappSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class WhatsappSessionFactory extends Factory
{
    protected $model = WhatsappSession::class;

    public function definition()
    {
        return [
            'phone' => $this->faker->e164PhoneNumber,
            'status' => 'active',
            'locale' => 'en',
            'provider_id' => Provider::factory(),
            'context' => [],
            'last_interacted_at' => now(),
        ];
    }
}
