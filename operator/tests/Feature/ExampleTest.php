<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // Root redirects to /expeditii/cautare (requires auth → further redirect to /login)
        $response = $this->get('/');

        $response->assertRedirect('/expeditii/cautare');
    }
}
