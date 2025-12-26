<?php

namespace Tests\Security;

use App\Models\Client;
use App\Models\Request as ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class XssPreventionTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_title_is_escaped_in_html_response(): void
    {
        $client = Client::factory()->create();
        $user = User::factory()->forClient($client)->create();

        $req = ServiceRequest::factory()->create([
            'client_id' => $client->id,
            'created_by' => $user->id,
            'title' => '<script>alert(1)</script>',
            'description' => 'Legit description',
        ]);

        $resp = $this->actingAs($user)->get(route('requests.show', $req));
        $resp->assertOk();

        // Blade/Livewire should escape raw HTML.
        $resp->assertDontSee('<script>alert(1)</script>', false);
        $resp->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}

