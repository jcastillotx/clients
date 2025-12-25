<?php

namespace App\Services\Storage;

use App\Contracts\StorageProviderInterface;
use RuntimeException;

/**
 * Foundation stub for Google Drive integration.
 *
 * TODO: implement using Google APIs (OAuth2).
 */
class GoogleDriveService implements StorageProviderInterface
{
    /** @var array<string, mixed> */
    protected array $credentials = [];

    public function connect(array $credentials): bool
    {
        $this->credentials = $credentials;
        return true;
    }

    public function disconnect(): bool
    {
        $this->credentials = [];
        return true;
    }

    public function listFiles(string $path): array
    {
        throw new RuntimeException('GoogleDriveService::listFiles not implemented yet.');
    }

    public function uploadFile(mixed $file, string $path): array
    {
        throw new RuntimeException('GoogleDriveService::uploadFile not implemented yet.');
    }

    public function downloadFile(string $fileId): mixed
    {
        throw new RuntimeException('GoogleDriveService::downloadFile not implemented yet.');
    }

    public function deleteFile(string $fileId): bool
    {
        throw new RuntimeException('GoogleDriveService::deleteFile not implemented yet.');
    }

    public function createFolder(string $name, string $parentPath): array
    {
        throw new RuntimeException('GoogleDriveService::createFolder not implemented yet.');
    }

    public function getFileUrl(string $fileId): ?string
    {
        throw new RuntimeException('GoogleDriveService::getFileUrl not implemented yet.');
    }

    public function getStorageUsage(): array
    {
        throw new RuntimeException('GoogleDriveService::getStorageUsage not implemented yet.');
    }
}

