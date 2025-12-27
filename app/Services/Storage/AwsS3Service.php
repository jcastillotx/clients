<?php

namespace App\Services\Storage;

use App\Contracts\StorageProviderInterface;
use App\Models\StorageConnection;
use App\Models\SyncedFile;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Carbon\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * AWS S3 provider integration.
 */
class AwsS3Service implements StorageProviderInterface
{
    /** @var array<string, mixed> */
    protected array $credentials = [];

    protected ?S3Client $client = null;

    protected ?StorageConnection $connection = null;

    public function __construct() {}

    public function useConnection(StorageConnection $connection): static
    {
        $this->connection = $connection;
        $this->credentials = (array) ($connection->credentials ?? []);
        $this->client = $this->makeClient($this->credentials);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    protected function makeClient(array $credentials): S3Client
    {
        $region = (string) ($credentials['region'] ?? '');
        $key = (string) ($credentials['access_key_id'] ?? $credentials['key'] ?? '');
        $secret = (string) ($credentials['secret_access_key'] ?? $credentials['secret'] ?? '');

        return new S3Client([
            'version' => 'latest',
            'region' => $region,
            'credentials' => [
                'key' => $key,
                'secret' => $secret,
            ],
        ]);
    }

    /**
     * Normalize and join prefixes.
     */
    protected function normalizePrefix(?string $prefix): string
    {
        $p = trim((string) $prefix);
        $p = trim($p, '/');

        return $p === '' ? '' : ($p.'/');
    }

    /**
     * @return array{bucket:string, base_prefix:string}
     */
    protected function bucketAndBasePrefix(): array
    {
        $bucket = (string) ($this->credentials['bucket'] ?? $this->credentials['bucket_name'] ?? '');
        $base = $this->normalizePrefix($this->credentials['folder_path'] ?? $this->credentials['prefix'] ?? '');

        return ['bucket' => $bucket, 'base_prefix' => $base];
    }

    protected function ensureReady(): void
    {
        if (! $this->client) {
            $this->client = $this->makeClient($this->credentials);
        }
    }

    protected function friendlyAwsError(\Throwable $e): string
    {
        if ($e instanceof AwsException) {
            $code = (string) $e->getAwsErrorCode();

            return match ($code) {
                'InvalidAccessKeyId', 'SignatureDoesNotMatch' => 'Invalid AWS credentials. Please verify Access Key and Secret Key.',
                'AccessDenied' => 'Access denied. Please ensure the IAM user has permissions for the bucket.',
                'NoSuchBucket' => 'Bucket not found. Please verify the bucket name and region.',
                default => $e->getAwsErrorMessage() ?: $e->getMessage(),
            };
        }

        return $e->getMessage();
    }

    public function connect(array $credentials): bool
    {
        $required = ['access_key_id', 'secret_access_key', 'region', 'bucket'];
        foreach ($required as $key) {
            if (! isset($credentials[$key]) || trim((string) $credentials[$key]) === '') {
                throw new RuntimeException("Missing required credential: {$key}");
            }
        }

        $this->credentials = [
            'access_key_id' => (string) $credentials['access_key_id'],
            'secret_access_key' => (string) $credentials['secret_access_key'],
            'region' => (string) $credentials['region'],
            'bucket' => (string) $credentials['bucket'],
            'folder_path' => (string) ($credentials['folder_path'] ?? ''),
        ];

        $this->client = $this->makeClient($this->credentials);
        $this->ensureReady();

        // Test connection
        try {
            $meta = $this->bucketAndBasePrefix();
            $this->client->listObjectsV2([
                'Bucket' => $meta['bucket'],
                'Prefix' => $meta['base_prefix'],
                'MaxKeys' => 1,
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException($this->friendlyAwsError($e));
        }

        // Persist to DB unless explicitly disabled
        $save = (bool) ($credentials['save'] ?? true);
        if (! $save) {
            return true;
        }

        $clientId = (int) ($credentials['client_id'] ?? 0);
        if ($clientId <= 0) {
            throw new RuntimeException('Missing required credential: client_id');
        }

        $isPrimary = (bool) ($credentials['is_primary'] ?? false);

        $connection = StorageConnection::query()->updateOrCreate(
            [
                'client_id' => $clientId,
                'provider' => 'aws_s3',
            ],
            [
                'credentials' => $this->credentials,
                'status' => 'connected',
                'is_primary' => $isPrimary,
                'last_synced_at' => now(),
            ]
        );

        if ($isPrimary) {
            StorageConnection::query()
                ->where('client_id', $clientId)
                ->where('id', '!=', $connection->id)
                ->update(['is_primary' => false]);
        }

        $this->connection = $connection;

        return true;
    }

    public function disconnect(): bool
    {
        if ($this->connection) {
            $this->connection->update(['status' => 'disconnected']);
        }

        $this->credentials = [];
        $this->client = null;
        $this->connection = null;

        return true;
    }

    public function listFiles(string $path): array
    {
        return $this->listFilesPaginated($path, null);
    }

    public function uploadFile(mixed $file, string $path): array
    {
        $this->ensureReady();
        if (! $this->connection) {
            throw new RuntimeException('No storage connection selected.');
        }

        $meta = $this->bucketAndBasePrefix();
        $bucket = $meta['bucket'];
        if ($bucket === '') {
            throw new RuntimeException('Bucket is not configured.');
        }

        $dir = $this->normalizePrefix($path);
        $base = $meta['base_prefix'];
        $prefix = $base.$dir;

        $originalName = null;
        $mime = null;
        $size = null;
        $stream = null;
        $sourcePath = null;

        if (is_object($file) && method_exists($file, 'getClientOriginalName')) {
            $originalName = (string) $file->getClientOriginalName();
            $mime = method_exists($file, 'getMimeType') ? (string) $file->getMimeType() : null;
            $size = method_exists($file, 'getSize') ? (int) $file->getSize() : null;
            $sourcePath = method_exists($file, 'getRealPath') ? $file->getRealPath() : null;
        }

        if (! $originalName) {
            throw new RuntimeException('Unsupported file type for upload.');
        }

        $key = $prefix.$originalName;
        if ($sourcePath && is_file($sourcePath)) {
            $stream = fopen($sourcePath, 'rb');
        }

        if (! $stream) {
            throw new RuntimeException('Unable to read uploaded file.');
        }

        try {
            $this->client->putObject([
                'Bucket' => $bucket,
                'Key' => $key,
                'Body' => $stream,
                'ACL' => 'private',
                'ContentType' => $mime ?: null,
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException($this->friendlyAwsError($e));
        } finally {
            try {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $synced = SyncedFile::query()->updateOrCreate(
            [
                'storage_connection_id' => $this->connection->id,
                'provider_file_id' => $key,
            ],
            [
                'document_id' => null,
                'file_name' => $originalName,
                'file_path' => $key,
                'file_size' => $size ?? 0,
                'mime_type' => $mime,
                'last_modified_at' => now(),
                'synced_at' => now(),
                'sync_status' => 'synced',
            ]
        );

        return [
            'id' => $key,
            'name' => $originalName,
            'path' => $key,
            'size' => (int) ($size ?? 0),
            'modified_date' => now()->toISOString(),
            'synced_file_id' => $synced->id,
        ];
    }

    public function downloadFile(string $fileId): mixed
    {
        $this->ensureReady();
        $meta = $this->bucketAndBasePrefix();
        $bucket = $meta['bucket'];
        if ($bucket === '') {
            throw new RuntimeException('Bucket is not configured.');
        }

        try {
            $cmd = $this->client->getCommand('GetObject', [
                'Bucket' => $bucket,
                'Key' => $fileId,
            ]);
            $req = $this->client->createPresignedRequest($cmd, '+60 minutes');

            return (string) $req->getUri();
        } catch (\Throwable $e) {
            throw new RuntimeException($this->friendlyAwsError($e));
        }
    }

    public function deleteFile(string $fileId): bool
    {
        $this->ensureReady();
        $meta = $this->bucketAndBasePrefix();
        $bucket = $meta['bucket'];
        if ($bucket === '') {
            throw new RuntimeException('Bucket is not configured.');
        }

        try {
            $this->client->deleteObject([
                'Bucket' => $bucket,
                'Key' => $fileId,
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException($this->friendlyAwsError($e));
        }

        if ($this->connection) {
            SyncedFile::query()
                ->where('storage_connection_id', $this->connection->id)
                ->where('provider_file_id', $fileId)
                ->delete();
        }

        return true;
    }

    public function createFolder(string $name, string $parentPath): array
    {
        $this->ensureReady();
        $meta = $this->bucketAndBasePrefix();
        $bucket = $meta['bucket'];
        if ($bucket === '') {
            throw new RuntimeException('Bucket is not configured.');
        }

        $base = $meta['base_prefix'];
        $parent = $this->normalizePrefix($parentPath);
        $folder = trim($name, '/');
        if ($folder === '') {
            throw new RuntimeException('Folder name is required.');
        }

        $key = $base.$parent.$folder.'/';

        try {
            $this->client->putObject([
                'Bucket' => $bucket,
                'Key' => $key,
                'Body' => '',
                'ACL' => 'private',
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException($this->friendlyAwsError($e));
        }

        return [
            'id' => $key,
            'name' => $folder,
            'path' => $key,
            'size' => 0,
            'modified_date' => now()->toISOString(),
            'type' => 'folder',
        ];
    }

    public function getFileUrl(string $fileId): ?string
    {
        return $this->downloadFile($fileId);
    }

    public function getStorageUsage(): array
    {
        $this->ensureReady();
        $meta = $this->bucketAndBasePrefix();
        $bucket = $meta['bucket'];
        if ($bucket === '') {
            throw new RuntimeException('Bucket is not configured.');
        }

        $prefix = $meta['base_prefix'];
        $token = null;
        $used = 0;

        try {
            do {
                $args = [
                    'Bucket' => $bucket,
                    'MaxKeys' => 1000,
                ];
                if ($prefix !== '') {
                    $args['Prefix'] = $prefix;
                }
                if ($token) {
                    $args['ContinuationToken'] = $token;
                }

                $result = $this->client->listObjectsV2($args);
                $contents = $result['Contents'] ?? [];
                foreach ($contents as $obj) {
                    $used += (int) ($obj['Size'] ?? 0);
                }

                $token = $result['NextContinuationToken'] ?? null;
            } while (! empty($result['IsTruncated']));
        } catch (\Throwable $e) {
            throw new RuntimeException($this->friendlyAwsError($e));
        }

        if ($this->connection) {
            $this->connection->update([
                'storage_used' => $used,
                'last_synced_at' => now(),
            ]);
        }

        return ['used' => $used, 'total' => $this->connection?->storage_limit];
    }

    /**
     * List files with optional continuation token.
     *
     * @return array{folders:array<int,array<string,mixed>>,files:array<int,array<string,mixed>>,next_token:?string,prefix:string}
     */
    public function listFilesPaginated(string $path, ?string $continuationToken = null): array
    {
        $this->ensureReady();
        $meta = $this->bucketAndBasePrefix();
        $bucket = $meta['bucket'];
        if ($bucket === '') {
            throw new RuntimeException('Bucket is not configured.');
        }

        $base = $meta['base_prefix'];
        $dir = trim($path, '/');
        $prefix = $base.($dir === '' ? '' : ($dir.'/'));

        try {
            $args = [
                'Bucket' => $bucket,
                'Prefix' => $prefix,
                'Delimiter' => '/',
                'MaxKeys' => 1000,
            ];
            if ($continuationToken) {
                $args['ContinuationToken'] = $continuationToken;
            }

            $result = $this->client->listObjectsV2($args);
        } catch (\Throwable $e) {
            throw new RuntimeException($this->friendlyAwsError($e));
        }

        $folders = [];
        foreach (($result['CommonPrefixes'] ?? []) as $cp) {
            $pfx = (string) ($cp['Prefix'] ?? '');
            if ($pfx === $prefix) {
                continue;
            }
            $name = trim(Str::after($pfx, $prefix), '/');
            $folders[] = [
                'id' => $pfx,
                'name' => $name,
                'path' => $pfx,
                'size' => 0,
                'modified_date' => null,
                'type' => 'folder',
            ];
        }

        $files = [];
        foreach (($result['Contents'] ?? []) as $obj) {
            $key = (string) ($obj['Key'] ?? '');
            if ($key === '' || $key === $prefix) {
                continue;
            }
            if (str_ends_with($key, '/')) {
                continue;
            } // folder marker
            $files[] = [
                'id' => $key,
                'name' => basename($key),
                'path' => $key,
                'size' => (int) ($obj['Size'] ?? 0),
                'modified_date' => isset($obj['LastModified']) ? Carbon::parse($obj['LastModified'])->toISOString() : null,
                'type' => 'file',
            ];
        }

        return [
            'folders' => $folders,
            'files' => $files,
            'next_token' => $result['NextContinuationToken'] ?? null,
            'prefix' => $prefix,
        ];
    }

    /**
     * Lightweight metadata fetch for a single key.
     *
     * @return array{size:int, modified_at:?string, mime_type:?string}
     */
    public function head(string $fileId): array
    {
        $this->ensureReady();
        $meta = $this->bucketAndBasePrefix();
        $bucket = $meta['bucket'];
        if ($bucket === '') {
            throw new RuntimeException('Bucket is not configured.');
        }

        try {
            $res = $this->client->headObject([
                'Bucket' => $bucket,
                'Key' => $fileId,
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException($this->friendlyAwsError($e));
        }

        $lm = $res['LastModified'] ?? null;

        return [
            'size' => (int) ($res['ContentLength'] ?? 0),
            'modified_at' => $lm ? Carbon::parse($lm)->toISOString() : null,
            'mime_type' => isset($res['ContentType']) ? (string) $res['ContentType'] : null,
        ];
    }
}
