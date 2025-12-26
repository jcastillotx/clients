<?php

namespace Tests\Performance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoadTestingPlaceholderTest extends TestCase
{
    use RefreshDatabase;

    public function test_load_testing_is_run_outside_phpunit(): void
    {
        $this->markTestSkipped('Example placeholder: use k6/Locust/JMeter for concurrent user load testing (see scripts/).');
    }
}

