<?php

namespace App\Services\Security;

use Aws\S3\S3Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * S3 Encrypted Storage Service
 *
 * Handles S3 storage with server-side encryption (SSE-S3 or SSE-KMS).
 * All data is encrypted at rest using AES-256 and transmitted via TLS 1.3.
 */
class S3EncryptedStorageService
{
    protected S3Client $client;

    protected string $bucket;

    protected string $region;

    protected ?string $kmsKeyId;

    protected EncryptionService $encryptionService;

    public function __construct(EncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
        $this->bucket = config('services.s3_encrypted.bucket', config('filesystems.disks.s3.bucket'));
        $this->region = config('services.s3_encrypted.region', config('filesystems.disks.s3.region', 'us-east-1'));
        $this->kmsKeyId = config('services.s3_encrypted.kms_key_id');

        $this->client = new S3Client([
            'version' => 'latest',
            'region' => $this->region,
            'credentials' => [
                'key' => config('services.s3_encrypted.key', config('filesystems.disks.s3.key')),
                'secret' => config('services.s3_encrypted.secret', config('filesystems.disks.s3.secret')),
            ],
            'http' => [
                'verify' => true, // Verify SSL certificates
                'connect_timeout' => 10,
                'timeout' => 60,
            ],
        ]);
    }

    /**
     * Upload a file with client-side and server-side encryption.
     *
     * @param  string  $contents  File contents (will be encrypted client-side first)
     * @param  string  $path  S3 object key/path
     * @param  string  $encryptionKey  Client-side encryption key
     * @param  array  $metadata  Additional metadata
     * @return array{path: string, encrypted: array, etag: string}
     */
    public function uploadEncrypted(
        string $contents,
        string $path,
        string $encryptionKey,
        array $metadata = []
    ): array {
        // Client-side encryption (AES-256-GCM)
        $encrypted = $this->encryptionService->encryptFile($contents, $encryptionKey);

        // Prepare server-side encryption parameters
        $params = [
            'Bucket' => $this->bucket,
            'Key' => $path,
            'Body' => base64_decode($encrypted['encrypted']),
            'ContentType' => $metadata['content_type'] ?? 'application/octet-stream',
            'Metadata' => array_merge($metadata, [
                'x-encryption-iv' => $encrypted['iv'],
                'x-encryption-tag' => $encrypted['tag'],
                'x-encryption-checksum' => $encrypted['checksum'],
                'x-encryption-algorithm' => EncryptionService::CIPHER,
            ]),
        ];

        // Use KMS if configured, otherwise SSE-S3
        if ($this->kmsKeyId) {
            $params['ServerSideEncryption'] = 'aws:kms';
            $params['SSEKMSKeyId'] = $this->kmsKeyId;
        } else {
            $params['ServerSideEncryption'] = 'AES256';
        }

        try {
            $result = $this->client->putObject($params);

            Log::info('File uploaded with encryption', [
                'path' => $path,
                'bucket' => $this->bucket,
                'encryption' => $this->kmsKeyId ? 'SSE-KMS' : 'SSE-S3',
            ]);

            return [
                'path' => $path,
                'encrypted' => [
                    'iv' => $encrypted['iv'],
                    'tag' => $encrypted['tag'],
                    'checksum' => $encrypted['checksum'],
                ],
                'etag' => $result['ETag'],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to upload encrypted file', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Failed to upload file: '.$e->getMessage());
        }
    }

    /**
     * Download and decrypt a file.
     *
     * @param  string  $path  S3 object key/path
     * @param  string  $encryptionKey  Client-side encryption key
     * @return string Decrypted file contents
     */
    public function downloadDecrypted(string $path, string $encryptionKey): string
    {
        try {
            $result = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            $encryptedContents = (string) $result['Body'];
            $metadata = $result['Metadata'] ?? [];

            $iv = $metadata['x-encryption-iv'] ?? null;
            $tag = $metadata['x-encryption-tag'] ?? null;
            $checksum = $metadata['x-encryption-checksum'] ?? null;

            if (! $iv || ! $tag) {
                throw new RuntimeException('Missing encryption metadata');
            }

            $decrypted = $this->encryptionService->decryptFile(
                base64_encode($encryptedContents),
                $encryptionKey,
                $iv,
                $tag
            );

            // Verify integrity
            if ($checksum && ! $this->encryptionService->verifyChecksum($decrypted, $checksum)) {
                throw new RuntimeException('File integrity check failed');
            }

            return $decrypted;
        } catch (\Exception $e) {
            Log::error('Failed to download encrypted file', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Failed to download file: '.$e->getMessage());
        }
    }

    /**
     * Upload a file from an UploadedFile instance.
     */
    public function uploadFile(
        UploadedFile $file,
        string $directory,
        string $encryptionKey,
        ?string $filename = null
    ): array {
        $filename = $filename ?? $file->hashName();
        $path = rtrim($directory, '/').'/'.$filename;

        return $this->uploadEncrypted(
            $file->getContent(),
            $path,
            $encryptionKey,
            [
                'content_type' => $file->getMimeType(),
                'original_name' => $file->getClientOriginalName(),
            ]
        );
    }

    /**
     * Delete an encrypted file.
     */
    public function delete(string $path): bool
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            Log::info('Encrypted file deleted', ['path' => $path]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete encrypted file', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if a file exists.
     */
    public function exists(string $path): bool
    {
        try {
            $this->client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get file metadata without downloading.
     */
    public function getMetadata(string $path): array
    {
        try {
            $result = $this->client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            return [
                'size' => $result['ContentLength'],
                'content_type' => $result['ContentType'],
                'last_modified' => $result['LastModified'],
                'etag' => $result['ETag'],
                'metadata' => $result['Metadata'] ?? [],
            ];
        } catch (\Exception $e) {
            throw new RuntimeException('Failed to get file metadata: '.$e->getMessage());
        }
    }

    /**
     * Generate a pre-signed URL for secure download.
     *
     * @param  string  $path  S3 object key/path
     * @param  int  $expiresInMinutes  URL expiration time
     */
    public function generatePresignedUrl(string $path, int $expiresInMinutes = 15): string
    {
        $command = $this->client->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key' => $path,
        ]);

        $request = $this->client->createPresignedRequest(
            $command,
            "+{$expiresInMinutes} minutes"
        );

        return (string) $request->getUri();
    }

    /**
     * Copy a file within the bucket.
     */
    public function copy(string $sourcePath, string $destinationPath): bool
    {
        try {
            $params = [
                'Bucket' => $this->bucket,
                'CopySource' => $this->bucket.'/'.$sourcePath,
                'Key' => $destinationPath,
            ];

            // Maintain encryption
            if ($this->kmsKeyId) {
                $params['ServerSideEncryption'] = 'aws:kms';
                $params['SSEKMSKeyId'] = $this->kmsKeyId;
            } else {
                $params['ServerSideEncryption'] = 'AES256';
            }

            $this->client->copyObject($params);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to copy encrypted file', [
                'source' => $sourcePath,
                'destination' => $destinationPath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * List files in a directory.
     *
     * @return array<int, array{path: string, size: int, last_modified: string}>
     */
    public function listFiles(string $prefix, int $maxKeys = 1000): array
    {
        try {
            $result = $this->client->listObjectsV2([
                'Bucket' => $this->bucket,
                'Prefix' => $prefix,
                'MaxKeys' => $maxKeys,
            ]);

            $files = [];
            foreach ($result['Contents'] ?? [] as $object) {
                $files[] = [
                    'path' => $object['Key'],
                    'size' => $object['Size'],
                    'last_modified' => $object['LastModified']->format('Y-m-d H:i:s'),
                ];
            }

            return $files;
        } catch (\Exception $e) {
            Log::error('Failed to list files', [
                'prefix' => $prefix,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get bucket name.
     */
    public function getBucket(): string
    {
        return $this->bucket;
    }

    /**
     * Check if KMS encryption is enabled.
     */
    public function isKmsEnabled(): bool
    {
        return $this->kmsKeyId !== null;
    }
}
