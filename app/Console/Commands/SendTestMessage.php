<?php

namespace App\Console\Commands;

use App\Models\Provider;
use App\Services\WhatsAppApiServiceFactory;
use Illuminate\Console\Command;
use Netflie\WhatsAppCloudApi\Message\Template\Component;

class SendTestMessage extends Command
{
    protected $signature = 'platform:send-test 
                            {provider : The slug of the provider to send from} 
                            {to : The recipient phone number in E.164 format} 
                            {--type=text : The type of message (text, image, document, audio, video, sticker, location, template)} 
                            {--text= : The text content for text messages or caption for media} 
                            {--link= : The public URL or Media ID for media messages} 
                            {--filename= : The filename for documents} 
                            {--template= : The name of the template to send}';

    protected $description = 'Sends a test message via a specific provider to test the WhatsAppApiService.';

    public function handle(WhatsAppApiServiceFactory $apiFactory): int
    {
        $providerSlug = $this->argument('provider');
        $to = $this->argument('to');
        $type = $this->option('type');

        $provider = Provider::where('slug', $providerSlug)->first();
        if (! $provider) {
            $this->error("Provider '{$providerSlug}' not found.");

            return self::FAILURE;
        }

        $this->info("Sending a '{$type}' message to {$to} via {$provider->name}...");

        $service = $apiFactory->make($provider);

        try {
            switch ($type) {
                case 'text':
                    $text = $this->option('text') ?? 'This is a test message from the Artisan command.';
                    $service->sendTextMessage($to, $text);
                    break;

                case 'image':
                    $link = $this->option('link');
                    if (! $link) {
                        $this->error('--link option is required for image messages.');

                        return self::FAILURE;
                    }
                    $caption = $this->option('text') ?? '';
                    $service->sendImage($to, $link, $caption);
                    break;

                case 'document':
                    $link = $this->option('link');
                    $filename = $this->option('filename');
                    if (! $link || ! $filename) {
                        $this->error('--link and --filename options are required for document messages.');

                        return self::FAILURE;
                    }
                    $caption = $this->option('text') ?? '';
                    $service->sendDocument($to, $link, $filename, $caption);
                    break;

                case 'audio':
                    $link = $this->option('link');
                    if (! $link) {
                        $this->error('--link option is required for audio messages.');
                        return self::FAILURE;
                    }
                    $service->sendAudio($to, $link);
                    break;

                case 'video':
                    $link = $this->option('link');
                    if (! $link) {
                        $this->error('--link option is required for video messages.');
                        return self::FAILURE;
                    }
                    $caption = $this->option('text') ?? '';
                    $service->sendVideo($to, $link, $caption);
                    break;

                case 'sticker':
                    $link = $this->option('link');
                    if (! $link) {
                        $this->error('--link option is required for sticker messages.');
                        return self::FAILURE;
                    }
                    $service->sendSticker($to, $link);
                    break;

                case 'location':
                    // Example: --text="29.37,47.97"
                    $coords = explode(',', $this->option('text', ''));
                    if (count($coords) !== 2) {
                        $this->error('For location, --text must be in the format "latitude,longitude"');
                        return self::FAILURE;
                    }
                    $service->sendLocation($to, (float) $coords[0], (float) $coords[1], 'Test Location');
                    break;

                case 'template':
                    $name = $this->option('template');
                    if (! $name) {
                        $this->error('--template option is required for template messages.');
                        return self::FAILURE;
                    }
                    // This is a simple example. Real components would be more complex.
                    $service->sendTemplate($to, $name, 'en_US');
                    break;

                default:
                    $this->error("Unsupported message type '{$type}'.");

                    return self::FAILURE;
            }
        } catch (\Throwable $e) {
            $this->error('An error occurred while sending the message: ' . $e->getMessage());
            Log::error('SendTestMessage command failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return self::FAILURE;
        }

        $this->info('Message sent successfully (check logs for API response).');

        return self::SUCCESS;
    }
}
