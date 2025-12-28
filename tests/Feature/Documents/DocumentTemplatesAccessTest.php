<?php

namespace Tests\Feature\Documents;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentTemplatesAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_is_redirected_away_from_templates(): void
    {
        $user = User::factory()->create([
            'client_id' => 1,
        ]);

        $this->actingAs($user)
            ->get('/documents/templates')
            ->assertRedirect('/documents');
    }
}

