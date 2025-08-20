<?php

namespace Database\Factories;

use App\Models\FlowTemplate;
use App\Models\FlowVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

class FlowVersionFactory extends Factory
{
    protected $model = FlowVersion::class;

    public function definition()
    {
        return [
            'flow_template_id' => FlowTemplate::factory(),
            'version' => 1,
            'is_stable' => true,
            'status' => 'published',
            'name' => 'v1',
            'definition' => ['screens' => []],
            'schema_json' => ['screens' => []],
            'published_at' => now(),
        ];
    }
}
