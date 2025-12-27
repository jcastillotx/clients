<?php

namespace Tests\Unit;

use App\Services\AutomationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomationRuleEvaluationTest extends TestCase
{
    use RefreshDatabase;

    public function test_automation_engine_evaluates_simple_and_grouped_conditions(): void
    {
        $engine = app(AutomationEngine::class);

        $ctx = [
            'request' => [
                'status' => 'pending',
                'estimated_cost' => 150,
                'title' => 'Website update',
            ],
            'client' => [
                'tier' => 'premium',
            ],
        ];

        $this->assertTrue($engine->evaluateConditions([
            'field' => 'request.status',
            'operator' => 'equals',
            'value' => 'pending',
        ], $ctx));

        $this->assertTrue($engine->evaluateConditions([
            'operator' => 'and',
            'rules' => [
                ['field' => 'client.tier', 'operator' => 'equals', 'value' => 'premium'],
                ['field' => 'request.estimated_cost', 'operator' => 'gt', 'value' => 100],
                ['field' => 'request.title', 'operator' => 'contains', 'value' => 'Website'],
            ],
        ], $ctx));

        $this->assertFalse($engine->evaluateConditions([
            'operator' => 'or',
            'rules' => [
                ['field' => 'request.status', 'operator' => 'equals', 'value' => 'completed'],
                ['field' => 'request.estimated_cost', 'operator' => 'lt', 'value' => 10],
            ],
        ], $ctx));
    }
}
