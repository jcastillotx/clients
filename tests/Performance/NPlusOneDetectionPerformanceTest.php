<?php

namespace Tests\Performance;

use App\Models\Client;
use App\Models\Request as ServiceRequest;
use App\Models\RequestAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NPlusOneDetectionPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_show_page_query_count_budget_example(): void
    {
        if (!getenv('RUN_PERFORMANCE_TESTS')) {
            $this->markTestSkipped('Set RUN_PERFORMANCE_TESTS=1 to run performance tests.');
        }

        $client = Client::factory()->create();
        $user = User::factory()->forClient($client)->create();

        $req = ServiceRequest::factory()->create([
            'client_id' => $client->id,
            'created_by' => $user->id,
        ]);

        foreach (range(1, 5) as $i) {
            RequestAttachment::create([
                'request_id' => $req->id,
                'uploaded_by' => $user->id,
                'filename' => "f{$i}.txt",
                'original_filename' => "f{$i}.txt",
                'file_path' => "requests/{$req->id}/f{$i}.txt",
                'mime_type' => 'text/plain',
                'file_size' => 10,
            ]);
        }

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->actingAs($user)->get(route('requests.show', $req))->assertOk();

        // Example budget. Tune this number; Livewire + activity log can add queries.
        $this->assertLessThan(60, $queries, "Request show triggered {$queries} queries (possible N+1).");
    }
}

