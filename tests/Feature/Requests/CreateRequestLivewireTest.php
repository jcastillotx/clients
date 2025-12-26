<?php

namespace Tests\Feature\Requests;

use App\Models\Client;
use App\Models\Request as ServiceRequest;
use App\Models\RequestAttachment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CreateRequestLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_create_request_with_attachment(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('attachments');

        $client = Client::factory()->create();
        $user = User::factory()->forClient($client)->create();
        $user->assignRole('client');

        $file = UploadedFile::fake()->create('brief.pdf', 12, 'application/pdf');

        Livewire::actingAs($user)
            ->test(\App\Http\Livewire\Requests\RequestCreate::class)
            ->set('title', 'Need help with website')
            ->set('description', str_repeat('Details ', 10))
            ->set('type', 'support')
            ->set('priority', 'medium')
            ->set('files', [$file])
            ->call('save')
            ->assertRedirect();

        $this->assertTrue(ServiceRequest::query()->where('client_id', $client->id)->where('title', 'Need help with website')->exists());

        $request = ServiceRequest::query()->where('client_id', $client->id)->latest('id')->firstOrFail();
        $this->assertSame('draft', $request->status);

        $attachment = RequestAttachment::query()->where('request_id', $request->id)->firstOrFail();
        Storage::disk('attachments')->assertExists($attachment->file_path);
    }
}

