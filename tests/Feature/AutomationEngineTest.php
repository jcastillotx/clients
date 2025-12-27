<?php

namespace Tests\Feature;

use App\Models\AutomationRule;
use App\Models\Client;
use App\Models\Request as ServiceRequest;
use App\Services\AutomationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AutomationEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_engine_matches_conditions_and_creates_invoice_action(): void
    {
        Mail::fake();

        $client = Client::factory()->create(['tier' => 'basic']);
        $req = ServiceRequest::factory()->create([
            'client_id' => $client->id,
            'priority' => 'urgent',
            'estimated_cost' => 250,
            'status' => 'pending',
        ]);

        $rule = AutomationRule::create([
            'name' => 'Urgent request -> invoice draft',
            'trigger' => 'request.created',
            'conditions' => [
                'operator' => 'and',
                'rules' => [
                    ['field' => 'request.priority', 'operator' => 'equals', 'value' => 'urgent'],
                ],
            ],
            'actions' => [
                ['type' => 'create_invoice', 'config' => []],
            ],
            'is_active' => true,
        ]);

        $engine = app(AutomationEngine::class);
        $engine->run('request.created', [
            'request' => $req->toArray(),
            'client' => $client->toArray(),
        ], $client->id);

        $this->assertDatabaseHas('automation_runs', [
            'automation_rule_id' => $rule->id,
            'trigger' => 'request.created',
            'matched' => 1,
        ]);

        $this->assertDatabaseHas('invoices', [
            'client_id' => $client->id,
            'request_id' => $req->id,
            'status' => 'draft',
        ]);
    }
}
