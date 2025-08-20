<?php

namespace Database\Factories;

use App\Models\FlowTemplate;
use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FlowTemplateFactory extends Factory
{
    protected $model = FlowTemplate::class;

    public function definition()
    {
        $name = $this->faker->words(3, true);
        return [
            'service_type_id' => ServiceType::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence,
        ];
    }
}
