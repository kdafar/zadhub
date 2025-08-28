<?php

namespace Tests\Unit;

use App\Services\FlowRenderer;
use Tests\TestCase;

class FlowRendererTest extends TestCase
{
    public function test_it_interpolates_variables_in_screen_data()
    {
        // 1. Arrange
        $renderer = new FlowRenderer;

        $screenConfig = [
            'id' => 'GREETING',
            'title' => 'Welcome, {{user.name}}!',
            'data' => [
                'text' => 'Your order #{{order.id}} is confirmed.',
                'footer_label' => 'View status for {{order.id}}',
            ],
        ];

        $context = [
            'user' => [
                'name' => 'John Doe',
            ],
            'order' => [
                'id' => '123-ABC',
            ],
        ];

        // 2. Act
        $rendered = $renderer->renderScreen($screenConfig, $context);

        // 3. Assert
        $this->assertEquals('Welcome, John Doe!', $rendered['title']);
        $this->assertEquals('Your order #123-ABC is confirmed.', $rendered['body']);
        $this->assertEquals('View status for 123-ABC', $rendered['footer']);
    }

    public function test_it_handles_missing_variables_gracefully()
    {
        // 1. Arrange
        $renderer = new FlowRenderer;

        $screenConfig = [
            'id' => 'GREETING',
            'title' => 'Hello, {{user.name}}!',
            'data' => [
                'text' => 'Your city is {{user.address.city}}.',
            ],
        ];

        $context = [
            'user' => [
                'name' => 'Jane',
            ],
        ];

        // 2. Act
        $rendered = $renderer->renderScreen($screenConfig, $context);

        // 3. Assert
        $this->assertEquals('Hello, Jane!', $rendered['title']);
        $this->assertEquals('Your city is {{user.address.city}}.', $rendered['body']);
    }
}
