<?php

namespace Database\Factories;

use App\Models\Provider;
use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProviderFactory extends Factory
{
    protected $model = Provider::class;

    public function definition()
    {
        $name = $this->faker->company;

        return [
            'service_type_id' => ServiceType::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'status' => 'active',
            'api_base_url' => $this->faker->url,
            'auth_type' => 'none',
            'is_sandbox' => false,
            'locale_defaults' => [],
            'feature_flags' => [],
            'is_active' => true,
            'callback_url' => $this->faker->url,
            'contact_email' => $this->faker->email,
            'contact_phone' => $this->faker->phoneNumber,
            'timezone' => 'UTC',
            'meta' => [],

        ];
    }
}
