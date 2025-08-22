<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // Force the session driver to 'array' for this test to avoid database issues.
        config()->set('session.driver', 'array');

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
