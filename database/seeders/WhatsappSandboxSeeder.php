<?php

namespace Database\Seeders;

use App\Models\Flow;
use App\Models\FlowTemplate;
use App\Models\FlowVersion;
use App\Models\Provider;
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WhatsappSandboxSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Ensure a Service
        $service = Service::query()->first();
        if (! $service) {
            $service = Service::create([
                'name_en' => 'Restaurant',
                'name_ar' => 'مطعم',
            ]);
        }

        // 2) Ensure a Provider
        $slug = Str::slug('Sandbox Provider');
        $provider = Provider::updateOrCreate(
            ['slug' => $slug],
            [
                'service_id' => $service->id,
                'name' => 'Sandbox Provider',
                'whatsapp_phone_number_id' => 'PNID_TEST_1',
                'api_token' => 'fake-token',
                'auth_type' => 'none',
                'is_active' => 1,
                'is_sandbox' => 1,
                'timezone' => 'UTC',
            ]
        );

        // 3) Ensure a FlowTemplate (since flow_versions.flow_template_id is NOT NULL)
        $template = FlowTemplate::query()->first();
        if (! $template) {
            $template = new FlowTemplate;
            // be defensive about columns:
            if (Schema::hasColumn('flow_templates', 'name')) {
                $template->name = 'Sandbox Template';
            }
            if (Schema::hasColumn('flow_templates', 'slug')) {
                $template->slug = Str::slug('Sandbox Template');
            }
            $template->save();
        }

        // 4) Flow JSON
        $builder = [
            'screens' => [
                [
                    'id' => 'WELCOME',
                    'type' => 'text_body',
                    'children' => [],
                    'data' => [
                        'text' => 'Welcome! Choose a cuisine next.',
                        'next_screen_id' => 'SELECT_CUISINE',
                    ],
                ],
                [
                    'id' => 'SELECT_CUISINE',
                    'type' => 'dropdown',
                    'children' => [],
                    'data' => [
                        'title' => 'Select cuisine',
                        'options' => [
                            ['label' => 'Indian', 'value' => 'indian'],
                            ['label' => 'Arabic', 'value' => 'arabic'],
                        ],
                        'next_on_choice' => [
                            'indian' => 'RESTAURANT_IN',
                            'arabic' => 'RESTAURANT_AR',
                        ],
                    ],
                ],
                [
                    'id' => 'RESTAURANT_IN',
                    'type' => 'text_body',
                    'children' => [],
                    'data' => ['text' => 'You picked Indian. Done!'],
                ],
                [
                    'id' => 'RESTAURANT_AR',
                    'type' => 'text_body',
                    'children' => [],
                    'data' => ['text' => 'You picked Arabic. Done!'],
                ],
            ],
        ];

        // 5) Flow and a published version
        $flow = Flow::updateOrCreate(
            ['provider_id' => $provider->id, 'trigger_keyword' => 'restaurant'],
            ['name' => 'Restaurant Demo', 'is_active' => true]
        );

        // ✅ Update existing template version (83, v1) if it exists, otherwise create it.
        //    This respects the unique index (flow_template_id, version).
        FlowVersion::updateOrCreate(
            ['flow_template_id' => $template->id, 'version' => 1],
            [
                'status' => 'published',
                'published_at' => now(),
                'definition' => $builder,   // keep for compatibility
                'schema_json' => $builder,   // NOT NULL column
                'service_id' => $provider->service_id,
                'provider_id' => $provider->id,
                'flow_id' => $flow->id,  // link this version to the flow
                // optional:
                // 'name' => 'v1',
                // 'is_stable' => 1,
            ]
        );
    }
}
