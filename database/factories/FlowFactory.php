<?php

namespace Database\Factories;

use App\Models\Flow;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FlowFactory extends Factory
{
    protected $model = Flow::class;

    public function definition()
    {
        $name = $this->faker->words(3, true);

        return [
            'provider_id' => Provider::factory(),
            'name' => $name,
            'trigger_keyword' => Str::slug($name),
            'is_active' => true,
        ];
    }
}
