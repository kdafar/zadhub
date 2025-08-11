<?php

namespace Database\Seeders;

use App\Models\FlowTemplate;
use App\Models\FlowVersion;
use App\Models\Provider;
use App\Models\Service;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create the Service Type
        $service = Service::firstOrCreate(
            ['name' => 'Restaurant & Cafe'],
            ['description' => 'For businesses that serve food and beverages.']
        );

        // 2. Create a Provider
        $provider = Provider::firstOrCreate(
            ['name' => "Gemini's Pizza"],
            ['service_id' => $service->id]
        );

        // 3. Create a Flow Template
        $template = FlowTemplate::firstOrCreate(
            ['name' => 'Standard Restaurant Order'],
            ['service_id' => $service->id, 'is_active' => true]
        );

        // 4. Create a Flow Version for the Template
        $builderData = [
            'screens' => [
                [
                    'id' => 'SCR_WELCOME',
                    'title' => 'Welcome Screen',
                    'footer_label' => 'Continue',
                    'children' => [
                        [
                            'type' => 'text_body',
                            'data' => ['text' => 'Welcome to our restaurant!', 'markdown' => false],
                        ],
                    ],
                ],
            ],
        ];

        $version = FlowVersion::create([
            'flow_template_id' => $template->id,
            'version_number' => 1,
            'builder_data' => $builderData,
            'changelog' => 'Initial version created by seeder.',
        ]);

        // --- THIS IS THE CRUCIAL FIX ---
        // 5. Set the new version as the "live" version for the template
        $template->update(['live_version_id' => $version->id]);
    }
}
