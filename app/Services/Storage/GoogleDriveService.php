<?php

namespace App\Services\Storage;

use App\Contracts\StorageProviderInterface;
use App\Models\StorageConnection;
use App\Models\SyncedFile;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\DriveFile;
use Google\Service\Oauth2 as GoogleOauth2;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class GoogleDriveService implements StorageProviderInterface
{
    /** @var array<string, mixed> */
    protected array $credentials = [];

    protected ?StorageConnection $connection = null;
    protected ?GoogleClient $client = null;
    protected ?GoogleDrive $drive = null;

    public function useConnection(StorageConnection $connection): static
    {
        $this->connection = $connection;
        $this->credentials = (array) ($connection->credentials ?? []);
        $this->client = $this->makeClient($this->credentials);
        $this->drive = new GoogleDrive($this->client);
        $this->refreshIfNeeded();
        return $this;
    }

    public function authorizationUrl(string $state): string
    {
        $client = $this->makeClient([]);
        $client->setState($state);
        return $client->createAuthUrl();
    }

    public function connect(array $credentials): bool
    {
        $clientId = (int) ($credentials['client_id'] ?? 0);
        if ($clientId <= 0) {
            throw new RuntimeException('Missing required credential: client_id');
        }

        $code = (string) ($credentials['authorization_code'] ?? '');
        if ($code === '') {
            throw new RuntimeException('Missing required credential: authorization_code');
        }

        $client = $this->makeClient([]);
        $token = $client->fetchAccessTokenWithAuthCode($code);
        if (isset($token['error'])) {
            throw new RuntimeException('Google OAuth token exchange failed: ' . (string) ($token['error_description'] ?? $token['error']));
        }

        $accessToken = (string) ($token['access_token'] ?? '');
        if ($accessToken === '') {
            throw new RuntimeException('Google OAuth token exchange failed: missing access_token.');
        }

        $refreshToken = (string) ($token['refresh_token'] ?? '');
        $expiresAt = null;
        if (isset($token['created'], $token['expires_in'])) {
            $expiresAt = now()->addSeconds((int) $token['expires_in'])->toIso8601String();
        }

        $client->setAccessToken($token);

        // Fetch user account email.
        $oauth2 = new GoogleOauth2($client);
        $me = $oauth2->userinfo->get();

        $accountEmail = (string) ($me->getEmail() ?? '');
        $accountId = (string) ($me->getId() ?? '');

        $folderId = (string) ($credentials['folder_id'] ?? '');
        $syncMode = (string) ($credentials['sync_mode'] ?? 'bidirectional'); // bidirectional|upload_only|download_only
        $isPrimary = (bool) ($credentials['is_primary'] ?? false);

        $this->credentials = [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_at' => $expiresAt,
            'scope' => (string) ($token['scope'] ?? ''),
            'token_type' => (string) ($token['token_type'] ?? ''),
            'account_email' => $accountEmail,
            'account_id' => $accountId,
            'folder_id' => $folderId,
            'sync_mode' => $syncMode,
        ];

        $connection = StorageConnection::query()->updateOrCreate(
            [
                'client_id' => $clientId,
                'provider' => 'google_drive',
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
            $creds = (array) ($this->connection->credentials ?? []);
            $creds['access_token'] = null;
            $creds['refresh_token'] = null;
            $this->connection->update([
                'status' => 'disconnected',
                'credentials' => $creds,
            ]);
        }

        $this->credentials = [];
        $this->connection = null;
        $this->client = null;
        $this->drive = null;
        return true;
    }

    /**
     * List files only (folders excluded), under a folder ID (or current base folder).
     */
    public function listFiles(string $path): array
    {
        $this->ensureReady();

        $folderId = $path !== '' ? $path : $this->baseFolderId();
        $folderId = $folderId ?: 'root';

        $q = sprintf("'%s' in parents and trashed=false and mimeType != 'application/vnd.google-apps.folder'", addslashes($folderId));

        $pageToken = null;
        $items = [];

        do {
            $params = [
                'q' => $q,
                'pageSize' => 100,
                'fields' => 'nextPageToken, files(id,name,mimeType,size,modifiedTime,webViewLink,createdTime)',
            ];
            if ($pageToken) {
                $params['pageToken'] = $pageToken;
            }

            $res = $this->drive->files->listFiles($params);
            foreach ($res->getFiles() as $f) {
                $items[] = [
                    'id' => (string) $f->getId(),
                    'name' => (string) $f->getName(),
                    'path' => (string) $f->getId(), // Drive uses IDs
                    'type' => 'file',
                    'size' => (int) ($f->getSize() ?? 0),
                    'modified_at' => (string) ($f->getModifiedTime() ?? ''),
                    'mime_type' => (string) ($f->getMimeType() ?? ''),
                    'web_view_link' => (string) ($f->getWebViewLink() ?? ''),
                ];
            }

            $pageToken = $res->getNextPageToken();
        } while ($pageToken);

        return $items;
    }

    /**
     * List folders under a folder ID.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listFolders(?string $folderId = null): array
    {
        $this->ensureReady();

        $folderId = $folderId ?: $this->baseFolderId() ?: 'root';
        $q = sprintf("'%s' in parents and trashed=false and mimeType = 'application/vnd.google-apps.folder'", addslashes($folderId));

        $pageToken = null;
        $folders = [];
        do {
            $params = [
                'q' => $q,
                'pageSize' => 100,
                'fields' => 'nextPageToken, files(id,name,modifiedTime)',
            ];
            if ($pageToken) {
                $params['pageToken'] = $pageToken;
            }
            $res = $this->drive->files->listFiles($params);
            foreach ($res->getFiles() as $f) {
                $folders[] = [
                    'id' => (string) $f->getId(),
                    'name' => (string) $f->getName(),
                    'type' => 'folder',
                    'modified_at' => (string) ($f->getModifiedTime() ?? ''),
                ];
            }
            $pageToken = $res->getNextPageToken();
        } while ($pageToken);

        return $folders;
    }

    public function uploadFile(mixed $file, string $path): array
    {
        $this->ensureReady();
        if (!$this->connection) {
            throw new RuntimeException('Google Drive connection not loaded.');
        }

        // $path is treated as "name" (Drive uses parent folder IDs, not paths).
        $name = trim($path, '/');
        if ($name === '') {
            throw new RuntimeException('Missing target file name.');
        }

        $parent = $this->baseFolderId() ?: 'root';

        $contents = null;
        $mime = 'application/octet-stream';
        $size = 0;

        if ($file instanceof UploadedFile) {
            $name = $file->getClientOriginalName() ?: $name;
            $mime = (string) ($file->getClientMimeType() ?: $mime);
            $size = (int) ($file->getSize() ?: 0);
            $contents = file_get_contents($file->getRealPath());
        } elseif (is_string($file) && is_file($file)) {
            $name = basename($file);
            $size = (int) (@filesize($file) ?: 0);
            $contents = file_get_contents($file);
        } elseif (is_string($file)) {
            $contents = $file;
            $size = strlen($file);
        } else {
            throw new RuntimeException('Unsupported upload file type.');
        }

        $meta = new DriveFile([
            'name' => $name,
            'parents' => [$parent],
        ]);

        $created = $this->drive->files->create($meta, [
            'data' => $contents,
            'mimeType' => $mime,
            'uploadType' => 'multipart',
            'fields' => 'id,name,mimeType,size,modifiedTime,webViewLink',
        ]);

        $id = (string) $created->getId();
        SyncedFile::query()->updateOrCreate(
            [
                'storage_connection_id' => $this->connection->id,
                'provider_file_id' => $id,
            ],
            [
                'document_id' => null,
                'file_name' => (string) $created->getName(),
                'file_path' => $id,
                'file_size' => (int) ($created->getSize() ?? $size),
                'mime_type' => (string) ($created->getMimeType() ?? $mime),
                'last_modified_at' => (string) ($created->getModifiedTime() ?? null) ?: null,
                'synced_at' => now(),
                'sync_status' => 'synced',
            ]
        );

        return [
            'id' => $id,
            'name' => (string) $created->getName(),
            'path' => $id,
            'size' => (int) ($created->getSize() ?? $size),
            'modified_at' => (string) ($created->getModifiedTime() ?? ''),
            'mime_type' => (string) ($created->getMimeType() ?? ''),
        ];
    }

    /**
     * Returns a stream download for binary files.
     *
     * @return array{stream:resource,file_name?:string,mime_type?:string}
     */
    public function downloadStream(string $fileId): array
    {
        $this->ensureReady();

        $meta = $this->drive->files->get($fileId, ['fields' => 'name,mimeType']);
        $name = (string) ($meta->getName() ?? $fileId);
        $mime = (string) ($meta->getMimeType() ?? 'application/octet-stream');

        $resp = $this->drive->files->get($fileId, ['alt' => 'media']);

        // Google API client returns PSR-7 stream in some versions; safest is to open a temp stream and copy.
        $tmp = fopen('php://temp', 'w+');
        if (is_string($resp)) {
            fwrite($tmp, $resp);
        } elseif (method_exists($resp, 'getBody')) {
            $body = $resp->getBody();
            while (!$body->eof()) {
                fwrite($tmp, $body->read(1024 * 1024));
            }
        } else {
            // Fallback: try casting.
            fwrite($tmp, (string) $resp);
        }
        rewind($tmp);

        return [
            'stream' => $tmp,
            'file_name' => $name,
            'mime_type' => $mime,
        ];
    }

    /**
     * Export Google Workspace files to a chosen mime type.
     *
     * @return array{stream:resource,file_name?:string,mime_type?:string}
     */
    public function downloadExport(string $fileId, string $exportMime): array
    {
        $this->ensureReady();

        $meta = $this->drive->files->get($fileId, ['fields' => 'name,mimeType']);
        $name = (string) ($meta->getName() ?? $fileId);

        $resp = $this->drive->files->export($fileId, $exportMime, ['alt' => 'media']);

        $tmp = fopen('php://temp', 'w+');
        if (is_string($resp)) {
            fwrite($tmp, $resp);
        } elseif (method_exists($resp, 'getBody')) {
            $body = $resp->getBody();
            while (!$body->eof()) {
                fwrite($tmp, $body->read(1024 * 1024));
            }
        } else {
            fwrite($tmp, (string) $resp);
        }
        rewind($tmp);

        $ext = match ($exportMime) {
            'application/pdf' => 'pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            default => 'bin',
        };

        return [
            'stream' => $tmp,
            'file_name' => Str::finish($name, '.' . $ext),
            'mime_type' => $exportMime,
        ];
    }

    public function downloadFile(string $fileId): mixed
    {
        // For compatibility with interface: return a stream bundle.
        return $this->downloadStream($fileId);
    }

    public function deleteFile(string $fileId): bool
    {
        $this->ensureReady();
        if (!$this->connection) {
            throw new RuntimeException('Google Drive connection not loaded.');
        }

        $this->drive->files->delete($fileId);

        SyncedFile::query()
            ->where('storage_connection_id', $this->connection->id)
            ->where('provider_file_id', $fileId)
            ->delete();

        return true;
    }

    public function createFolder(string $name, string $parentPath): array
    {
        $this->ensureReady();

        $parentId = $parentPath !== '' ? $parentPath : ($this->baseFolderId() ?: 'root');

        $folder = new DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId],
        ]);

        $created = $this->drive->files->create($folder, [
            'fields' => 'id,name',
        ]);

        return [
            'id' => (string) $created->getId(),
            'name' => (string) $created->getName(),
            'path' => (string) $created->getId(),
            'type' => 'folder',
        ];
    }

    public function getFileUrl(string $fileId): ?string
    {
        $this->ensureReady();
        $meta = $this->drive->files->get($fileId, ['fields' => 'webViewLink']);
        return $meta->getWebViewLink() ?: null;
    }

    public function getStorageUsage(): array
    {
        $this->ensureReady();

        $about = $this->drive->about->get(['fields' => 'storageQuota']);
        $quota = $about->getStorageQuota();

        $used = (int) ($quota?->getUsage() ?? 0);
        $limit = $quota?->getLimit();

        if ($this->connection) {
            $this->connection->update([
                'storage_used' => $used,
                'storage_limit' => $limit !== null ? (int) $limit : null,
                'last_synced_at' => $this->connection->last_synced_at ?? now(),
            ]);
        }

        return [
            'used' => $used,
            'total' => $limit !== null ? (int) $limit : null,
        ];
    }

    /**
     * Share a folder with a user (team member) email.
     */
    public function shareFolderWithUser(string $folderId, string $email, string $role = 'writer'): void
    {
        $this->ensureReady();

        $perm = new \Google\Service\Drive\Permission([
            'type' => 'user',
            'role' => $role,
            'emailAddress' => $email,
        ]);

        $this->drive->permissions->create($folderId, $perm, [
            'sendNotificationEmail' => false,
        ]);
    }

    protected function ensureReady(): void
    {
        if (!$this->client || !$this->drive) {
            if (!$this->connection) {
                throw new RuntimeException('Google Drive connection not loaded.');
            }
            $this->useConnection($this->connection);
        }

        $accessToken = (string) ($this->credentials['access_token'] ?? '');
        if ($accessToken === '') {
            throw new RuntimeException('Google access token missing.');
        }
    }

    protected function makeClient(array $token): GoogleClient
    {
        $id = (string) config('storage-providers.google_drive.client_id');
        $secret = (string) config('storage-providers.google_drive.client_secret');
        $redirect = (string) config('storage-providers.google_drive.redirect_uri');

        if ($id === '' || $secret === '' || $redirect === '') {
            throw new RuntimeException('Google Drive OAuth is not configured. Set GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI.');
        }

        $client = new GoogleClient();
        $client->setClientId($id);
        $client->setClientSecret($secret);
        $client->setRedirectUri($redirect);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setIncludeGrantedScopes(true);
        $client->setScopes([
            \Google\Service\Drive::DRIVE_FILE,
            \Google\Service\Drive::DRIVE_METADATA_READONLY,
            \Google\Service\Oauth2::USERINFO_EMAIL,
        ]);

        if (!empty($token)) {
            $client->setAccessToken($token);
        }

        return $client;
    }

    protected function refreshIfNeeded(): void
    {
        if (!$this->connection || !$this->client) {
            return;
        }

        $token = $this->buildTokenArrayFromCredentials($this->credentials);
        if (!empty($token)) {
            $this->client->setAccessToken($token);
        }

        if (!$this->client->isAccessTokenExpired()) {
            return;
        }

        $refreshToken = (string) ($this->credentials['refresh_token'] ?? '');
        if ($refreshToken === '') {
            $this->connection->update(['status' => 'error']);
            return;
        }

        try {
            $newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
            if (isset($newToken['error'])) {
                $this->connection->update(['status' => 'error']);
                return;
            }

            $this->credentials['access_token'] = (string) ($newToken['access_token'] ?? $this->credentials['access_token'] ?? '');
            if (isset($newToken['expires_in'])) {
                $this->credentials['expires_at'] = now()->addSeconds((int) $newToken['expires_in'])->toIso8601String();
            }

            // Google may not return refresh_token on refresh; keep existing.
            $this->connection->update([
                'credentials' => $this->credentials,
                'status' => 'connected',
            ]);
            $this->connection->refresh();
        } catch (Throwable $e) {
            $this->connection->update(['status' => 'error']);
        }
    }

    /** @return array<string, mixed> */
    protected function buildTokenArrayFromCredentials(array $creds): array
    {
        $access = (string) ($creds['access_token'] ?? '');
        if ($access === '') {
            return [];
        }

        $token = [
            'access_token' => $access,
            'refresh_token' => (string) ($creds['refresh_token'] ?? ''),
            'token_type' => (string) ($creds['token_type'] ?? 'Bearer'),
        ];

        // If we have expires_at, convert to "created + expires_in" isn't necessary; client will treat missing as non-expiring.
        // But setting expires_in helps isAccessTokenExpired().
        $expiresAt = (string) ($creds['expires_at'] ?? '');
        if ($expiresAt !== '') {
            try {
                $seconds = max(0, now()->diffInSeconds(\Carbon\Carbon::parse($expiresAt), false));
                $token['created'] = time();
                $token['expires_in'] = $seconds;
            } catch (Throwable $e) {
                // ignore
            }
        }

        return $token;
    }

    protected function baseFolderId(): ?string
    {
        $id = trim((string) ($this->credentials['folder_id'] ?? ''));
        return $id !== '' ? $id : null;
    }
}
