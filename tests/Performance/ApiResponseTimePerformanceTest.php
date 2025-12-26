<?php

namespace Tests\Performance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiResponseTimePerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_response_time_budget_example(): void
    {
        if (!getenv('RUN_PERFORMANCE_TESTS')) {
            $this->markTestSkipped('Set RUN_PERFORMANCE_TESTS=1 to run performance tests.');
        }

        $user = User::factory()->create();

        $start = microtime(true);
        $this->actingAs($user)->get('/dashboard')->assertOk();
        $elapsedMs = (microtime(true) - $start) * 1000;

        // Example budget. Tune for your infra/CI.
        $this->assertLessThan(2000, $elapsedMs, 'Dashboard exceeded response time budget.');
    }
}

