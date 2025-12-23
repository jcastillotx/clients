<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_redirect_response(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }
}
