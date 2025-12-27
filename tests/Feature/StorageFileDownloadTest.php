<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\StorageConnection;
use App\Models\StorageFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageFileDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_download_own_storage_file(): void
    {
        Storage::fake('testdisk');

        $client = Client::factory()->create();
        $user = User::factory()->create(['client_id' => $client->id]);

        Storage::disk('testdisk')->put('foo.txt', 'hello');

        $connection = StorageConnection::create([
            'client_id' => $client->id,
            'provider' => 's3',
            'name' => 'Test',
            'disk' => 'testdisk',
            'status' => 'active',
            'is_primary' => true,
        ]);

        $file = StorageFile::create([
            'storage_connection_id' => $connection->id,
            'path' => 'foo.txt',
            'filename' => 'foo.txt',
            'extension' => 'txt',
            'size_bytes' => 5,
        ]);

        $this->actingAs($user)
            ->get(route('storage.files.download', $file))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_client_cannot_download_other_clients_storage_file(): void
    {
        Storage::fake('testdisk');

        $clientA = Client::factory()->create();
        $clientB = Client::factory()->create();
        $userB = User::factory()->create(['client_id' => $clientB->id]);

        Storage::disk('testdisk')->put('secret.txt', 'secret');

        $connection = StorageConnection::create([
            'client_id' => $clientA->id,
            'provider' => 's3',
            'name' => 'A',
            'disk' => 'testdisk',
            'status' => 'active',
            'is_primary' => true,
        ]);

        $file = StorageFile::create([
            'storage_connection_id' => $connection->id,
            'path' => 'secret.txt',
            'filename' => 'secret.txt',
            'extension' => 'txt',
            'size_bytes' => 6,
        ]);

        $this->actingAs($userB)
            ->get(route('storage.files.download', $file))
            ->assertForbidden();
    }
}
