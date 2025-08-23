<?php

namespace Tests\Unit;

use App\Models\Provider;
use App\Models\ServiceType;
use App\Models\WhatsappSession;
use App\Services\WhatsAppMessageHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class WhatsAppMessageHandlerSystemMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function getSystemMessage(WhatsAppMessageHandler $handler, WhatsappSession $session, string $key, array $replacements = []): string
    {
        $method = new ReflectionMethod(WhatsAppMessageHandler::class, 'getSystemMessage');
        $method->setAccessible(true);

        return $method->invoke($handler, $session, $key, $replacements);
    }

    public function test_it_retrieves_a_localized_system_message_from_service_type()
    {
        // 1. Arrange
        $serviceType = ServiceType::factory()->create([
            'message_templates' => [
                'en' => [
                    'fallback' => 'English fallback.',
                ],
                'ar' => [
                    'fallback' => 'Fallback in Arabic.',
                ],
            ],
        ]);
        $provider = Provider::factory()->create(['service_type_id' => $serviceType->id]);
        $session = WhatsappSession::factory()->create([
            'provider_id' => $provider->id,
            'locale' => 'ar',
        ]);

        $handler = $this->app->make(WhatsAppMessageHandler::class);

        // 2. Act
        $message = $this->getSystemMessage($handler, $session, 'fallback');

        // 3. Assert
        $this->assertEquals('Fallback in Arabic.', $message);
    }

    public function test_it_uses_default_fallback_when_template_is_missing()
    {
        $serviceType = ServiceType::factory()->create(['message_templates' => []]); // No templates
        $provider = Provider::factory()->create(['service_type_id' => $serviceType->id]);
        $session = WhatsappSession::factory()->create(['provider_id' => $provider->id]);
        $handler = $this->app->make(WhatsAppMessageHandler::class);

        $message = $this->getSystemMessage($handler, $session, 'fallback');

        $this->assertEquals("I couldn't understand your message. Please reply with a valid keyword to continue.", $message);
    }

    public function test_it_replaces_placeholders_in_the_message()
    {
        $serviceType = ServiceType::factory()->create([
            'message_templates' => [
                'en' => [
                    'welcome' => 'Hello {{name}}, welcome to {{service}}!',
                ],
            ],
        ]);
        $provider = Provider::factory()->create(['service_type_id' => $serviceType->id]);
        $session = WhatsappSession::factory()->create(['provider_id' => $provider->id]);
        $handler = $this->app->make(WhatsAppMessageHandler::class);

        $message = $this->getSystemMessage($handler, $session, 'welcome', [
            'name' => 'John',
            'service' => 'Awesome Service',
        ]);

        $this->assertEquals('Hello John, welcome to Awesome Service!', $message);
    }
}
