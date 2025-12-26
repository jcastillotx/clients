<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Document;
use App\Models\DocumentPermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_download_own_document_by_default(): void
    {
        Storage::fake('documents');

        $client = Client::factory()->create();
        $user = User::factory()->create(['client_id' => $client->id]);

        Storage::disk('documents')->put('docs/a.txt', 'hello');

        $doc = Document::create([
            'client_id' => $client->id,
            'uploaded_by' => $user->id,
            'title' => 'A',
            'filename' => 'a.txt',
            'original_filename' => 'a.txt',
            'file_path' => 'docs/a.txt',
            'mime_type' => 'text/plain',
            'file_size' => 5,
            'category' => 'other',
            'is_public' => false,
        ]);

        $this->actingAs($user)
            ->get(route('documents.download', $doc))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_client_cannot_download_document_when_permission_denies_download(): void
    {
        Storage::fake('documents');

        $client = Client::factory()->create();
        $user = User::factory()->create(['client_id' => $client->id]);

        Storage::disk('documents')->put('docs/a.txt', 'hello');

        $doc = Document::create([
            'client_id' => $client->id,
            'uploaded_by' => $user->id,
            'title' => 'A',
            'filename' => 'a.txt',
            'original_filename' => 'a.txt',
            'file_path' => 'docs/a.txt',
            'mime_type' => 'text/plain',
            'file_size' => 5,
            'category' => 'other',
            'is_public' => false,
        ]);

        DocumentPermission::create([
            'document_id' => $doc->id,
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'can_view' => true,
            'can_download' => false,
            'can_upload_version' => false,
            'can_delete' => false,
        ]);

        $this->actingAs($user)
            ->get(route('documents.download', $doc))
            ->assertForbidden();
    }

    public function test_client_cannot_view_document_when_permission_denies_view(): void
    {
        Storage::fake('documents');

        $client = Client::factory()->create();
        $user = User::factory()->create(['client_id' => $client->id]);

        Storage::disk('documents')->put('docs/a.txt', 'hello');

        $doc = Document::create([
            'client_id' => $client->id,
            'uploaded_by' => $user->id,
            'title' => 'A',
            'filename' => 'a.txt',
            'original_filename' => 'a.txt',
            'file_path' => 'docs/a.txt',
            'mime_type' => 'text/plain',
            'file_size' => 5,
            'category' => 'other',
            'is_public' => false,
        ]);

        DocumentPermission::create([
            'document_id' => $doc->id,
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'can_view' => false,
            'can_download' => true,
            'can_upload_version' => false,
            'can_delete' => false,
        ]);

        $this->actingAs($user)
            ->get(route('documents.show', $doc))
            ->assertForbidden();
    }

    public function test_viewer_redirect_is_forbidden_when_download_denied(): void
    {
        Storage::fake('documents');

        $client = Client::factory()->create();
        $user = User::factory()->create(['client_id' => $client->id]);

        Storage::disk('documents')->put('docs/a.txt', 'hello');

        $doc = Document::create([
            'client_id' => $client->id,
            'uploaded_by' => $user->id,
            'title' => 'A',
            'filename' => 'a.txt',
            'original_filename' => 'a.txt',
            'file_path' => 'docs/a.txt',
            'mime_type' => 'text/plain',
            'file_size' => 5,
            'category' => 'other',
            'is_public' => false,
        ]);

        DocumentPermission::create([
            'document_id' => $doc->id,
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'can_view' => true,
            'can_download' => false,
            'can_upload_version' => false,
            'can_delete' => false,
        ]);

        $this->actingAs($user)
            ->get(route('documents.viewer.document', [$doc, 'office']))
            ->assertForbidden();
    }
}

