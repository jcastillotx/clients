<?php

namespace App\Services\Storage;

use App\Contracts\StorageProviderInterface;
use InvalidArgumentException;

class StorageManager
{
    /**
     * Resolve a provider implementation by key.
     *
     * @param  'aws_s3'|'dropbox'|'google_drive'  $provider
     */
    public function provider(string $provider): StorageProviderInterface
    {
        return match ($provider) {
            'aws_s3' => app(AwsS3Service::class),
            'dropbox' => app(DropboxService::class),
            'google_drive' => app(GoogleDriveService::class),
            default => throw new InvalidArgumentException("Unknown storage provider: {$provider}"),
        };
    }
}

