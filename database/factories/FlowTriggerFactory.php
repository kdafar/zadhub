<?php

namespace Database\Factories;

use App\Models\FlowTrigger;
use App\Models\FlowVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

class FlowTriggerFactory extends Factory
{
    protected $model = FlowTrigger::class;

    public function definition()
    {
        return [
            'keyword' => $this->faker->word,
            'flow_version_id' => FlowVersion::factory(),
            'is_active' => true,
            'priority' => 10,
        ];
    }
}
