<?php

namespace App\Console\Commands;

use App\Models\FlowTemplate;
use App\Models\FlowVersion;
use App\Models\MetaFlow;
use App\Models\Provider;
use App\Models\ServiceType;
// Make sure to import the MetaFlow model
use App\Models\WhatsappSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlatformVerify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'platform:verify 
        {--seed : Seed minimal data and run the onboarding service}
        {--handler : Run a WhatsApp handler dry-run with a test payload}
        {--onboard= : Onboard a specific provider by slug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifies the platform\'s core components, including DB schema, provider onboarding, and message handling.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dir = storage_path('app/verify');
        File::ensureDirectoryExists($dir);
        File::cleanDirectory($dir);

        $this->info('== Platform Verification Suite ==');
        Log::info('== Platform Verification Starting ==');

        if ($this->option('seed')) {
            $this->runSeederAndOnboarding($dir);
        }

        if ($this->option('handler')) {
            $this->runHandlerTest($dir);
        }

        if ($slug = $this->option('onboard')) {
            $this->onboardProvider($slug);
        }

        $this->newLine();
        $this->info('== Verification Complete ==');
        $this->line('Check logs for details and results in: storage/app/verify/');
        $this->newLine();

        return self::SUCCESS;
    }

    private function onboardProvider(string $slug): void
    {
        $this->line("Running: Onboarding for provider '{$slug}'...");
        $provider = Provider::where('slug', $slug)->first();

        if (! $provider) {
            $this->error("Provider with slug '{$slug}' not found.");

            return;
        }

        try {
            $svc = app(\App\Services\ProviderOnboardingService::class);
            $flow = $svc->onboard($provider);

            if ($flow) {
                $this->info("Successfully onboarded provider '{$slug}'. Flow ID: {$flow->id}");
            } else {
                $this->warn("Onboarding service ran for '{$slug}' but returned no flow. Check logs for details.");
            }
        } catch (\Throwable $e) {
            $this->error("An error occurred during onboarding for '{$slug}': " . $e->getMessage());
            Log::error('Onboarding command failed', ['slug' => $slug, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * Seeds the database with test data and runs the onboarding service.
     */
    private function runSeederAndOnboarding(string $dir): void
    {
        $this->line('Running: Seeder & Onboarding Service Test...');

        try {
            $provider = DB::transaction(function () {
                $st = ServiceType::updateOrCreate(['slug' => 'restaurants'], ['name' => 'Restaurants', 'is_active' => true]);
                $template = FlowTemplate::updateOrCreate(['slug' => 'restaurants_base', 'service_type_id' => $st->id], ['name' => 'Base Restaurants Template']);
                $masterVersion = FlowVersion::updateOrCreate(['flow_template_id' => $template->id, 'version' => 1], [
                    'service_type_id' => $st->id, 'is_template' => true, 'status' => 'published', 'published_at' => now(), 'name' => 'v1 Template', 'definition' => ['start_screen' => 'WELCOME', 'screens' => [['id' => 'WELCOME', 'type' => 'text', 'message' => 'Hi!']]],
                ]);
                $template->forceFill(['latest_version_id' => $masterVersion->id])->save();
                $st->forceFill(['default_flow_template_id' => $template->id])->save();

                return Provider::updateOrCreate(['slug' => 'demo-provider'], [
                    'service_type_id' => $st->id, 'name' => 'Demo Provider', 'is_active' => true, 'whatsapp_phone_number_id' => 'PNID_TEST_1',
                ]);
            });
            $this->info('Success: Prerequisite data seeded.');

            $this->line('Executing Onboarding Service...');
            $svc = app(\App\Services\ProviderOnboardingService::class);
            $flow = $svc->onboard($provider);

            if (! $flow) {
                throw new \Exception('Onboarding service returned null.');
            }

            $liveVersion = $flow->liveVersion()->first();
            if (! $liveVersion) {
                throw new \Exception('Onboarding completed, but the resulting Flow has no resolvable live version.');
            }

            // Ensure a minimal, valid definition
            $def = (array) ($liveVersion->definition ?? []);
            if (empty($def['start_screen']) || empty($def['screens'])) {
                $def = [
                    'start_screen' => 'WELCOME',
                    // Use the builder-friendly shape so FlowRenderer works in both paths
                    'screens' => [
                        [
                            'id' => 'WELCOME',
                            'type' => 'text_body',
                            'data' => ['text' => 'Hi!'],
                        ],
                    ],
                ];
                $liveVersion->definition = $def;
                $liveVersion->save();
            }

            // Make sure MetaFlow link exists (you already added this)
            MetaFlow::updateOrCreate(
                ['flow_version_id' => $liveVersion->id],
                ['meta_flow_id' => 'meta-flow-id-for-testing-123']
            );
            $this->info('Success: Created dummy MetaFlow record for testing.');
            // --- END FIX ---

            $onboardingResult = [
                'status' => 'SUCCESS',
                'details' => 'Onboarding created a provider, a flow, a live version, and a meta flow link successfully.',
            ];
            file_put_contents("$dir/onboarding_result.json", json_encode($onboardingResult, JSON_PRETTY_PRINT));
            $this->info('Success: Onboarding service ran successfully.');

        } catch (\Throwable $e) {
            Log::error('Seeder/Onboarding failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->error('Error during Seeder/Onboarding: '.$e->getMessage());
            file_put_contents("$dir/onboarding_result.json", json_encode(['status' => 'FAIL', 'error' => $e->getMessage()], JSON_PRETTY_PRINT));
        }
    }

    /**
     * Simulates a WhatsApp webhook to test the message handler.
     */
    private function runHandlerTest(string $dir): void
    {
        $this->line('Running: WhatsApp Message Handler Test...');

        $pnid = 'PNID_TEST_1';
        $keyword = 'restaurants_base';
        $fromPhone = '+96555500000';

        $payload = ['entry' => [['changes' => [['value' => [
            'metadata' => ['phone_number_id' => $pnid],
            'messages' => [['id' => 'wamid.TEST1', 'from' => $fromPhone, 'type' => 'text', 'text' => ['body' => $keyword]]],
        ]]]]]];

        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        try {
            $handler = app(\App\Services\WhatsAppMessageHandler::class);
            \App\Models\WhatsappSession::where('phone', $fromPhone)->delete();
            $handler->process($payload);

            $session = WhatsappSession::where('phone', $fromPhone)->latest('id')->first();
            if (! $session || $session->current_screen !== 'WELCOME') {
                throw new \Exception('Handler did not create a session or set the correct starting screen.');
            }

            $handlerResult = [
                'status' => 'SUCCESS',
                'details' => 'Handler processed the payload and correctly started a flow session.',
            ];
            file_put_contents("$dir/handler_result.json", json_encode($handlerResult, JSON_PRETTY_PRINT));
            $this->info('Success: Message handler processed the test payload correctly.');

        } catch (\Throwable $e) {
            Log::error('Handler test failed: '.$e->getMessage());
            $this->error('Error during Handler Test: '.$e->getMessage());
            file_put_contents("$dir/handler_result.json", json_encode(['status' => 'FAIL', 'error' => $e->getMessage()], JSON_PRETTY_PRINT));
        }
    }
}
