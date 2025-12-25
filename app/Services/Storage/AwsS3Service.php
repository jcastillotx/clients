<?php

namespace App\Services\Storage;

use App\Contracts\StorageProviderInterface;
use RuntimeException;

/**
 * Foundation stub for AWS S3 provider integration.
 *
 * TODO: implement using AWS SDK (league/flysystem-aws-s3-v3 or aws/aws-sdk-php).
 */
class AwsS3Service implements StorageProviderInterface
{
    /** @var array<string, mixed> */
    protected array $credentials = [];

    public function connect(array $credentials): bool
    {
        $this->credentials = $credentials;
        // TODO: validate credentials + perform a lightweight API call.
        return true;
    }

    public function disconnect(): bool
    {
        $this->credentials = [];
        return true;
    }

    public function listFiles(string $path): array
    {
        throw new RuntimeException('AwsS3Service::listFiles not implemented yet.');
    }

    public function uploadFile(mixed $file, string $path): array
    {
        throw new RuntimeException('AwsS3Service::uploadFile not implemented yet.');
    }

    public function downloadFile(string $fileId): mixed
    {
        throw new RuntimeException('AwsS3Service::downloadFile not implemented yet.');
    }

    public function deleteFile(string $fileId): bool
    {
        throw new RuntimeException('AwsS3Service::deleteFile not implemented yet.');
    }

    public function createFolder(string $name, string $parentPath): array
    {
        // S3 is object-storage; folders are prefixes.
        throw new RuntimeException('AwsS3Service::createFolder not implemented yet.');
    }

    public function getFileUrl(string $fileId): ?string
    {
        // Could return a signed URL.
        throw new RuntimeException('AwsS3Service::getFileUrl not implemented yet.');
    }

    public function getStorageUsage(): array
    {
        // S3 does not provide an account-wide quota in the same way; return used + null limit.
        return ['used' => 0, 'total' => null];
    }
}

