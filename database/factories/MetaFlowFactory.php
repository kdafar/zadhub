<?php

namespace Database\Factories;

use App\Models\FlowVersion;
use App\Models\MetaFlow;
use Illuminate\Database\Eloquent\Factories\Factory;

class MetaFlowFactory extends Factory
{
    protected $model = MetaFlow::class;

    public function definition()
    {
        return [
            'flow_version_id' => FlowVersion::factory(),
            'meta_flow_id' => 'meta-flow-' . $this->faker->uuid,
            'status' => 'published',
            'published_at' => now(),
        ];
    }
}
