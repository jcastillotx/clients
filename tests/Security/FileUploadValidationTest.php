<?php

namespace Tests\Security;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FileUploadValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_document_upload_requires_file(): void
    {
        Storage::fake('documents');

        $client = Client::factory()->create();
        $user = User::factory()->forClient($client)->create();

        Sanctum::actingAs($user, ['write']);

        $this->postJson('/api/v1/documents/upload', [
            'title' => 'No file',
        ])->assertStatus(422);
    }

    public function test_api_document_upload_rejects_oversized_file(): void
    {
        Storage::fake('documents');

        $client = Client::factory()->create();
        $user = User::factory()->forClient($client)->create();

        Sanctum::actingAs($user, ['write']);

        // Validation max is 51200 KB (~50MB). Use a slightly larger fake file.
        $big = UploadedFile::fake()->create('too-big.bin', 51201, 'application/octet-stream');

        $this->withHeader('Accept', 'application/json')->post('/api/v1/documents/upload', [
            'file' => $big,
        ])->assertStatus(422);
    }
}

