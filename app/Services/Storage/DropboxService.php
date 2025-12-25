<?php

namespace App\Services\Storage;

use App\Contracts\StorageProviderInterface;
use RuntimeException;

/**
 * Foundation stub for Dropbox integration.
 *
 * TODO: implement using Dropbox HTTP API (OAuth2).
 */
class DropboxService implements StorageProviderInterface
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
        throw new RuntimeException('DropboxService::listFiles not implemented yet.');
    }

    public function uploadFile(mixed $file, string $path): array
    {
        throw new RuntimeException('DropboxService::uploadFile not implemented yet.');
    }

    public function downloadFile(string $fileId): mixed
    {
        throw new RuntimeException('DropboxService::downloadFile not implemented yet.');
    }

    public function deleteFile(string $fileId): bool
    {
        throw new RuntimeException('DropboxService::deleteFile not implemented yet.');
    }

    public function createFolder(string $name, string $parentPath): array
    {
        throw new RuntimeException('DropboxService::createFolder not implemented yet.');
    }

    public function getFileUrl(string $fileId): ?string
    {
        throw new RuntimeException('DropboxService::getFileUrl not implemented yet.');
    }

    public function getStorageUsage(): array
    {
        throw new RuntimeException('DropboxService::getStorageUsage not implemented yet.');
    }
}

