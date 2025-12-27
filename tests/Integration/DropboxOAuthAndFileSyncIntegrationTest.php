<?php

namespace Tests\Integration;

use App\Models\Client;
use App\Services\Storage\DropboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DropboxOAuthAndFileSyncIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dropbox_oauth_connect_and_list_files(): void
    {
        if (!getenv('RUN_INTEGRATION_TESTS')) {
            $this->markTestSkipped('Set RUN_INTEGRATION_TESTS=1 to run external integration tests.');
        }

        if (!getenv('DROPBOX_APP_KEY') || !getenv('DROPBOX_APP_SECRET') || !getenv('DROPBOX_REDIRECT_URI')) {
            $this->markTestSkipped('Missing DROPBOX_APP_KEY / DROPBOX_APP_SECRET / DROPBOX_REDIRECT_URI.');
        }

        $authCode = getenv('DROPBOX_AUTHORIZATION_CODE') ?: '';
        if ($authCode === '') {
            $this->markTestSkipped('Missing DROPBOX_AUTHORIZATION_CODE (OAuth code from Dropbox redirect).');
        }

        $client = Client::factory()->create();
        $svc = app(DropboxService::class);

        $svc->connect([
            'client_id' => $client->id,
            'authorization_code' => $authCode,
            'folder_path' => getenv('DROPBOX_FOLDER_PATH') ?: '',
            'is_primary' => true,
        ]);

        $files = $svc->listFiles('');
        $this->assertIsArray($files);

        // Optional incremental sync example (uses cursor stored in connection credentials).
        $processed = $svc->syncChanges(50);
        $this->assertIsInt($processed);
    }
}

