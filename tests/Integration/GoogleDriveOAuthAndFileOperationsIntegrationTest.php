<?php

namespace Tests\Integration;

use App\Models\Client;
use App\Services\Storage\GoogleDriveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleDriveOAuthAndFileOperationsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_drive_oauth_connect_and_list_files(): void
    {
        if (!getenv('RUN_INTEGRATION_TESTS')) {
            $this->markTestSkipped('Set RUN_INTEGRATION_TESTS=1 to run external integration tests.');
        }

        if (!getenv('GOOGLE_CLIENT_ID') || !getenv('GOOGLE_CLIENT_SECRET') || !getenv('GOOGLE_REDIRECT_URI')) {
            $this->markTestSkipped('Missing GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET / GOOGLE_REDIRECT_URI.');
        }

        $authCode = getenv('GOOGLE_AUTHORIZATION_CODE') ?: '';
        if ($authCode === '') {
            $this->markTestSkipped('Missing GOOGLE_AUTHORIZATION_CODE (OAuth code from Google redirect).');
        }

        $client = Client::factory()->create();
        $svc = app(GoogleDriveService::class);

        $svc->connect([
            'client_id' => $client->id,
            'authorization_code' => $authCode,
            // Optional: restrict to a folder ID in Drive
            'folder_id' => getenv('GOOGLE_DRIVE_FOLDER_ID') ?: '',
            'is_primary' => true,
        ]);

        $files = $svc->listFiles(''); // lists under configured folder_id or root
        $this->assertIsArray($files);
    }
}

