<?php

namespace Tests\Unit;

use App\Services\Storage\AwsS3Service;
use App\Services\Storage\DropboxService;
use App\Services\Storage\GoogleDriveService;
use App\Services\Storage\StorageManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorageManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_storage_manager_resolves_known_providers(): void
    {
        $mgr = app(StorageManager::class);

        $this->assertInstanceOf(AwsS3Service::class, $mgr->provider('aws_s3'));
        $this->assertInstanceOf(DropboxService::class, $mgr->provider('dropbox'));
        $this->assertInstanceOf(GoogleDriveService::class, $mgr->provider('google_drive'));
    }

    public function test_storage_manager_rejects_unknown_provider(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(StorageManager::class)->provider('nope');
    }
}
