<?php

namespace App\Services\Storage;

use App\Contracts\StorageProviderInterface;
use App\Models\StorageConnection;
use App\Models\SyncedFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Spatie\Dropbox\Client as DropboxClient;
use Throwable;

/**
 * Dropbox integration using OAuth 2.0 + spatie/dropbox-api.
 */
class DropboxService implements StorageProviderInterface
{
    /** @var array<string, mixed> */
    protected array $credentials = [];

    protected ?DropboxClient $client = null;

    protected ?StorageConnection $connection = null;

    public function useConnection(StorageConnection $connection): static
    {
        $this->connection = $connection;
        $this->credentials = (array) ($connection->credentials ?? []);
        $this->client = $this->makeClient($connection);
        return $this;
    }

    /**
     * Build Dropbox OAuth authorization URL.
     */
    public function authorizationUrl(string $state): string
    {
        $appKey = (string) config('storage-providers.dropbox.app_key');
        $redirect = (string) config('storage-providers.dropbox.redirect_uri');
        if ($appKey === '' || $redirect === '') {
            throw new RuntimeException('Dropbox OAuth is not configured. Set DROPBOX_APP_KEY and DROPBOX_REDIRECT_URI.');
        }

        // token_access_type=offline requests refresh_token when app is configured for short-lived tokens.
        $params = http_build_query([
            'client_id' => $appKey,
            'response_type' => 'code',
            'redirect_uri' => $redirect,
            'state' => $state,
            'token_access_type' => 'offline',
        ]);

        return 'https://www.dropbox.com/oauth2/authorize?' . $params;
    }

    public function connect(array $credentials): bool
    {
        $clientId = (int) ($credentials['client_id'] ?? 0);
        if ($clientId <= 0) {
            throw new RuntimeException('Missing required credential: client_id');
        }

        $appKey = (string) config('storage-providers.dropbox.app_key');
        $appSecret = (string) config('storage-providers.dropbox.app_secret');
        $redirect = (string) config('storage-providers.dropbox.redirect_uri');
        if ($appKey === '' || $appSecret === '' || $redirect === '') {
            throw new RuntimeException('Dropbox OAuth is not configured. Set DROPBOX_APP_KEY, DROPBOX_APP_SECRET, DROPBOX_REDIRECT_URI.');
        }

        $authorizationCode = (string) ($credentials['authorization_code'] ?? '');
        if ($authorizationCode === '') {
            throw new RuntimeException('Missing required credential: authorization_code');
        }

        // Exchange code for access token.
        $resp = Http::asForm()
            ->withBasicAuth($appKey, $appSecret)
            ->post('https://api.dropboxapi.com/oauth2/token', [
                'code' => $authorizationCode,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirect,
            ]);

        if (!$resp->successful()) {
            $msg = (string) ($resp->json('error_description') ?? $resp->body());
            throw new RuntimeException('Dropbox OAuth token exchange failed: ' . $msg);
        }

        $body = (array) $resp->json();
        $accessToken = (string) ($body['access_token'] ?? '');
        if ($accessToken === '') {
            throw new RuntimeException('Dropbox OAuth token exchange failed: missing access_token.');
        }

        $expiresAt = null;
        if (isset($body['expires_in'])) {
            $expiresAt = now()->addSeconds((int) $body['expires_in'])->toIso8601String();
        }

        $folderPath = trim((string) ($credentials['folder_path'] ?? ''), '/');
        $isPrimary = (bool) ($credentials['is_primary'] ?? false);

        // Verify connection with account info.
        $client = new DropboxClient($accessToken);
        try {
            $acct = $client->getAccountInfo();
        } catch (Throwable $e) {
            throw new RuntimeException('Dropbox connection verification failed: ' . $e->getMessage());
        }

        $accountId = (string) Arr::get($acct, 'account_id', '');
        $accountEmail = (string) Arr::get($acct, 'email', '');

        $this->credentials = [
            'access_token' => $accessToken,
            'refresh_token' => (string) ($body['refresh_token'] ?? ''),
            'expires_at' => $expiresAt,
            'scope' => (string) ($body['scope'] ?? ''),
            'account_id' => $accountId,
            'account_email' => $accountEmail,
            'folder_path' => $folderPath,
            // Cursor stored for webhook-driven incremental sync.
            'cursor' => null,
        ];

        $connection = StorageConnection::query()->updateOrCreate(
            [
                'client_id' => $clientId,
                'provider' => 'dropbox',
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

        $this->useConnection($connection);

        return true;
    }

    public function disconnect(): bool
    {
        if ($this->connection) {
            try {
                $this->ensureReady();
                $this->client?->revokeToken();
            } catch (Throwable $e) {
                // ignore (token may already be revoked)
            }

            $creds = (array) ($this->connection->credentials ?? []);
            $creds['access_token'] = null;
            $creds['refresh_token'] = null;
            $this->connection->update([
                'status' => 'disconnected',
                'credentials' => $creds,
            ]);
        }

        $this->credentials = [];
        $this->client = null;
        $this->connection = null;
        return true;
    }

    public function listFiles(string $path): array
    {
        $this->ensureReady();

        $fullPath = $this->fullPath($path);
        $result = $this->client->listFolder($fullPath, false);

        $entries = (array) ($result['entries'] ?? []);
        $cursor = (string) ($result['cursor'] ?? '');
        $hasMore = (bool) ($result['has_more'] ?? false);

        while ($hasMore) {
            $next = $this->client->listFolderContinue($cursor);
            $entries = array_merge($entries, (array) ($next['entries'] ?? []));
            $cursor = (string) ($next['cursor'] ?? $cursor);
            $hasMore = (bool) ($next['has_more'] ?? false);
        }

        return collect($entries)->map(function ($e) {
            $tag = (string) ($e['.tag'] ?? '');
            $pathDisplay = (string) ($e['path_display'] ?? $e['path_lower'] ?? '');

            return [
                'id' => (string) ($e['id'] ?? $pathDisplay),
                'name' => (string) ($e['name'] ?? ''),
                'path' => $pathDisplay,
                'type' => $tag, // file|folder|deleted
                'size' => (int) ($e['size'] ?? 0),
                'modified_at' => (string) ($e['server_modified'] ?? ''),
                'mime_type' => null,
            ];
        })->values()->all();
    }

    public function uploadFile(mixed $file, string $path): array
    {
        $this->ensureReady();
        if (!$this->connection) {
            throw new RuntimeException('Dropbox connection not loaded.');
        }

        $target = $this->fullPath($path);

        $contents = $file;
        $originalName = null;
        $size = null;
        $mime = null;

        if ($file instanceof UploadedFile) {
            $originalName = $file->getClientOriginalName();
            $size = (int) $file->getSize();
            $mime = (string) $file->getClientMimeType();
            $contents = fopen($file->getRealPath(), 'r');
        } elseif (is_string($file) && is_file($file)) {
            $originalName = basename($file);
            $size = @filesize($file) ?: null;
            $contents = fopen($file, 'r');
        }

        $meta = $this->client->upload($target, $contents, 'add', true);

        $providerId = (string) ($meta['id'] ?? '');
        $fileName = (string) ($meta['name'] ?? $originalName ?? basename($target));
        $filePath = (string) ($meta['path_display'] ?? $meta['path_lower'] ?? $target);
        $fileSize = (int) ($meta['size'] ?? $size ?? 0);
        $modified = (string) ($meta['server_modified'] ?? null);

        if ($providerId !== '') {
            SyncedFile::query()->updateOrCreate(
                [
                    'storage_connection_id' => $this->connection->id,
                    'provider_file_id' => $providerId,
                ],
                [
                    'document_id' => null,
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'mime_type' => $mime,
                    'last_modified_at' => $modified ?: null,
                    'synced_at' => now(),
                    'sync_status' => 'synced',
                ]
            );
        }

        return [
            'id' => $providerId ?: $filePath,
            'name' => $fileName,
            'path' => $filePath,
            'size' => $fileSize,
            'modified_at' => $modified,
        ];
    }

    public function downloadFile(string $fileId): mixed
    {
        $this->ensureReady();
        $link = $this->client->getTemporaryLink($fileId);
        return $link;
    }

    public function deleteFile(string $fileId): bool
    {
        $this->ensureReady();
        if (!$this->connection) {
            throw new RuntimeException('Dropbox connection not loaded.');
        }

        $this->client->delete($fileId);

        SyncedFile::query()
            ->where('storage_connection_id', $this->connection->id)
            ->where(function ($q) use ($fileId) {
                $q->where('provider_file_id', $fileId)->orWhere('file_path', $fileId);
            })
            ->delete();

        return true;
    }

    public function createFolder(string $name, string $parentPath): array
    {
        $this->ensureReady();
        $parent = trim($parentPath, '/');
        $folderName = trim($name, '/');
        $path = trim($parent . '/' . $folderName, '/');

        $meta = $this->client->createFolder($this->fullPath($path));

        return [
            'id' => (string) ($meta['id'] ?? ''),
            'name' => (string) ($meta['name'] ?? $folderName),
            'path' => (string) ($meta['path_display'] ?? $meta['path_lower'] ?? ''),
            'type' => 'folder',
        ];
    }

    public function getFileUrl(string $fileId): ?string
    {
        // Prefer temporary links for secure access (expires ~4 hours).
        return (string) $this->downloadFile($fileId);
    }

    public function getStorageUsage(): array
    {
        $this->ensureReady();

        $body = $this->client->rpcEndpointRequest('users/get_space_usage');
        $used = (int) ($body['used'] ?? 0);

        $allocation = (array) ($body['allocation'] ?? []);
        $total = null;
        if (($allocation['.tag'] ?? '') === 'individual') {
            $total = (int) ($allocation['allocated'] ?? 0);
        } elseif (($allocation['.tag'] ?? '') === 'team') {
            $total = (int) ($allocation['allocated'] ?? 0);
        }

        return ['used' => $used, 'total' => $total];
    }

    /**
     * Incremental sync using stored cursor (used by webhook).
     *
     * @return int number of processed entries
     */
    public function syncChanges(int $maxEntries = 500): int
    {
        $this->ensureReady();
        if (!$this->connection) {
            throw new RuntimeException('Dropbox connection not loaded.');
        }

        $creds = (array) ($this->connection->credentials ?? []);
        $cursor = (string) ($creds['cursor'] ?? '');
        $processed = 0;

        if ($cursor === '') {
            // Initial seed: get cursor for base folder.
            $seed = $this->client->listFolder($this->fullPath(''), true);
            $cursor = (string) ($seed['cursor'] ?? '');
            $entries = (array) ($seed['entries'] ?? []);
            $processed += $this->applySyncEntries($entries, $maxEntries - $processed);
        }

        while ($cursor !== '' && $processed < $maxEntries) {
            $res = $this->client->listFolderContinue($cursor);
            $cursor = (string) ($res['cursor'] ?? $cursor);
            $entries = (array) ($res['entries'] ?? []);
            $processed += $this->applySyncEntries($entries, $maxEntries - $processed);

            $hasMore = (bool) ($res['has_more'] ?? false);
            if (!$hasMore) {
                break;
            }
        }

        $creds['cursor'] = $cursor ?: ($creds['cursor'] ?? null);
        $this->connection->update([
            'credentials' => $creds,
            'last_synced_at' => now(),
            'status' => 'connected',
        ]);

        return $processed;
    }

    protected function applySyncEntries(array $entries, int $limit): int
    {
        if ($limit <= 0) {
            return 0;
        }

        $count = 0;
        foreach ($entries as $e) {
            if ($count >= $limit) {
                break;
            }

            $tag = (string) ($e['.tag'] ?? '');
            $providerId = (string) ($e['id'] ?? '');
            $path = (string) ($e['path_lower'] ?? $e['path_display'] ?? '');

            if ($tag === 'deleted') {
                if ($providerId !== '') {
                    SyncedFile::query()
                        ->where('storage_connection_id', $this->connection->id)
                        ->where('provider_file_id', $providerId)
                        ->delete();
                } elseif ($path !== '') {
                    SyncedFile::query()
                        ->where('storage_connection_id', $this->connection->id)
                        ->where('file_path', $path)
                        ->delete();
                }
                $count++;
                continue;
            }

            if ($tag !== 'file') {
                // Skip folders for synced_files table (we only track files).
                continue;
            }

            if ($providerId === '') {
                continue;
            }

            SyncedFile::query()->updateOrCreate(
                [
                    'storage_connection_id' => $this->connection->id,
                    'provider_file_id' => $providerId,
                ],
                [
                    'document_id' => null,
                    'file_name' => (string) ($e['name'] ?? ''),
                    'file_path' => (string) ($e['path_lower'] ?? $e['path_display'] ?? ''),
                    'file_size' => (int) ($e['size'] ?? 0),
                    'mime_type' => null,
                    'last_modified_at' => (string) ($e['server_modified'] ?? null) ?: null,
                    'synced_at' => now(),
                    'sync_status' => 'synced',
                ]
            );

            $count++;
        }

        return $count;
    }

    protected function ensureReady(): void
    {
        if (!$this->client) {
            if (!$this->connection) {
                throw new RuntimeException('Dropbox connection not loaded.');
            }
            $this->client = $this->makeClient($this->connection);
        }

        if (!$this->client->getAccessToken()) {
            throw new RuntimeException('Dropbox access token missing.');
        }
    }

    protected function makeClient(StorageConnection $connection): DropboxClient
    {
        $appKey = (string) config('storage-providers.dropbox.app_key');
        $appSecret = (string) config('storage-providers.dropbox.app_secret');
        $redirect = (string) config('storage-providers.dropbox.redirect_uri');

        $provider = new DropboxConnectionTokenProvider($connection, $appKey, $appSecret, $redirect);
        return new DropboxClient($provider);
    }

    protected function fullPath(string $relativePath): string
    {
        $base = trim((string) ($this->credentials['folder_path'] ?? ''), '/');
        $rel = trim($relativePath, '/');

        if ($base === '' && $rel === '') {
            return '';
        }
        if ($base === '') {
            return '/' . $rel;
        }
        if ($rel === '') {
            return '/' . $base;
        }

        return '/' . $base . '/' . $rel;
    }
}

