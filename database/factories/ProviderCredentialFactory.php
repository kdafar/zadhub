<?php

namespace Database\Factories;

use App\Models\Provider;
use App\Models\ProviderCredential;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;

class ProviderCredentialFactory extends Factory
{
    protected $model = ProviderCredential::class;

    public function definition()
    {
        return [
            'provider_id' => Provider::factory(),
            'key_name' => 'api_key',
            'secret_encrypted' => Crypt::encryptString($this->faker->password),
        ];
    }
}
