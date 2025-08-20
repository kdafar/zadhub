<?php

namespace Database\Factories;

use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ServiceTypeFactory extends Factory
{
    protected $model = ServiceType::class;

    public function definition()
    {
        $name = $this->faker->company;
        return [
            'code' => Str::slug($name),
            'slug' => Str::slug($name),
            'name' => $name,
            'name_en' => $name,
            'name_ar' => $name,
            'description' => $this->faker->sentence,
            'default_locale' => 'en',
            'is_active' => true,
            'meta' => [],
        ];
    }
}
