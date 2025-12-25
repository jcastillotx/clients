<?php

namespace App\Jobs;

use App\Models\StorageConnection;
use App\Models\StorageSyncLog;
use App\Models\SyncedFile;
use App\Models\User;
use App\Notifications\StorageQuotaWarningNotification;
use App\Notifications\StorageSyncFailedNotification;
use App\Services\Storage\AwsS3Service;
use App\Services\Storage\DropboxService;
use App\Services\Storage\GoogleDriveService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SyncStorageConnection implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $connectionId,
        public int $maxFiles = 500,
    ) {}

    public function handle(): void
    {
        $connection = StorageConnection::query()->with('client')->find($this->connectionId);
        if (!$connection) {
            return;
        }

        $log = StorageSyncLog::create([
            'storage_connection_id' => $connection->id,
            'status' => 'running',
            'files_processed' => 0,
            'started_at' => now(),
        ]);

        try {
            $processed = match ($connection->provider) {
                'aws_s3' => $this->syncS3($connection),
                'dropbox' => $this->syncDropbox($connection),
                'google_drive' => $this->syncGoogleDrive($connection),
                default => 0,
            };

            // Update usage & quota warnings.
            $usage = match ($connection->provider) {
                'aws_s3' => app(AwsS3Service::class)->useConnection($connection)->getStorageUsage(),
                'dropbox' => app(DropboxService::class)->useConnection($connection)->getStorageUsage(),
                'google_drive' => app(GoogleDriveService::class)->useConnection($connection)->getStorageUsage(),
                default => ['used' => (int) $connection->storage_used, 'total' => $connection->storage_limit ? (int) $connection->storage_limit : null],
            };

            $used = (int) ($usage['used'] ?? 0);
            $total = isset($usage['total']) ? ($usage['total'] === null ? null : (int) $usage['total']) : null;

            if ($total && $total > 0) {
                $pct = $used / $total;
                if ($pct >= 0.8) {
                    $shouldNotify = $connection->quota_warned_80_at === null || $connection->quota_warned_80_at->lt(now()->subDays(7));
                    if ($shouldNotify) {
                        $this->notifyClientUsers($connection, new StorageQuotaWarningNotification($connection, $used, $total));
                        $connection->update(['quota_warned_80_at' => now()]);
                    }
                }
            }

            $connection->update([
                'status' => 'connected',
                'last_synced_at' => now(),
            ]);

            $this->applyConflictRules($connection);

            $log->update([
                'status' => 'success',
                'files_processed' => $processed,
                'finished_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $connection->update([
                'status' => 'error',
                'last_sync_failed_at' => now(),
            ]);

            $log->update([
                'status' => 'error',
                'finished_at' => now(),
                'message' => $e->getMessage(),
            ]);

            $shouldNotify = $connection->sync_failed_notified_at === null || $connection->sync_failed_notified_at->lt(now()->subHours(6));
            if ($shouldNotify) {
                $this->notifyClientUsers($connection, new StorageSyncFailedNotification($connection, $e->getMessage()));
                $connection->update(['sync_failed_notified_at' => now()]);
            }

            throw $e;
        }
    }

    protected function notifyClientUsers(StorageConnection $connection, $notification): void
    {
        $users = User::query()
            ->where('client_id', $connection->client_id)
            ->role(['client'])
            ->whereNotNull('email')
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, $notification);
    }

    protected function syncDropbox(StorageConnection $connection): int
    {
        return app(DropboxService::class)->useConnection($connection)->syncChanges($this->maxFiles);
    }

    protected function syncGoogleDrive(StorageConnection $connection): int
    {
        $svc = app(GoogleDriveService::class)->useConnection($connection);

        $base = (string) data_get($connection->credentials, 'folder_id', '');
        $base = $base !== '' ? $base : 'root';

        // BFS folder traversal (limited).
        $queue = [$base];
        $processed = 0;
        $seen = [];

        while (!empty($queue) && $processed < $this->maxFiles) {
            $folderId = array_shift($queue);
            if (isset($seen[$folderId])) {
                continue;
            }
            $seen[$folderId] = true;

            // Add subfolders.
            foreach ($svc->listFolders($folderId) as $f) {
                if ($processed >= $this->maxFiles) break;
                $id = (string) ($f['id'] ?? '');
                if ($id !== '' && !isset($seen[$id])) {
                    $queue[] = $id;
                }
            }

            // Upsert files.
            foreach ($svc->listFiles($folderId) as $f) {
                if ($processed >= $this->maxFiles) break;
                $id = (string) ($f['id'] ?? '');
                if ($id === '') continue;

                SyncedFile::query()->updateOrCreate(
                    [
                        'storage_connection_id' => $connection->id,
                        'provider_file_id' => $id,
                    ],
                    [
                        'document_id' => null,
                        'request_id' => null,
                        'contract_id' => null,
                        'file_name' => (string) ($f['name'] ?? ''),
                        'file_path' => (string) ($f['path'] ?? $id),
                        'file_size' => (int) ($f['size'] ?? 0),
                        'mime_type' => (string) ($f['mime_type'] ?? null) ?: null,
                        'last_modified_at' => (string) ($f['modified_at'] ?? null) ?: null,
                        'synced_at' => now(),
                        'sync_status' => 'synced',
                    ]
                );
                $processed++;
            }
        }

        return $processed;
    }

    protected function syncS3(StorageConnection $connection): int
    {
        $svc = app(AwsS3Service::class)->useConnection($connection);

        $processed = 0;
        $stack = [['path' => '', 'token' => null]];

        while (!empty($stack) && $processed < $this->maxFiles) {
            $frame = array_pop($stack);
            $path = (string) ($frame['path'] ?? '');
            $token = $frame['token'] ?? null;

            $page = $svc->listFilesPaginated($path, $token);

            // Queue subfolders.
            foreach (($page['folders'] ?? []) as $folder) {
                $name = (string) ($folder['name'] ?? '');
                if ($name === '') continue;
                $sub = trim(($path === '' ? $name : ($path . '/' . $name)), '/');
                $stack[] = ['path' => $sub, 'token' => null];
            }

            // Upsert files.
            foreach (($page['files'] ?? []) as $f) {
                if ($processed >= $this->maxFiles) break;
                $id = (string) ($f['id'] ?? '');
                if ($id === '') continue;

                SyncedFile::query()->updateOrCreate(
                    [
                        'storage_connection_id' => $connection->id,
                        'provider_file_id' => $id,
                    ],
                    [
                        'document_id' => null,
                        'request_id' => null,
                        'contract_id' => null,
                        'file_name' => (string) ($f['name'] ?? basename($id)),
                        'file_path' => (string) ($f['path'] ?? $id),
                        'file_size' => (int) ($f['size'] ?? 0),
                        'mime_type' => null,
                        'last_modified_at' => (string) ($f['modified_date'] ?? null) ?: null,
                        'synced_at' => now(),
                        'sync_status' => 'synced',
                    ]
                );
                $processed++;
            }

            $next = $page['next_token'] ?? null;
            if ($next) {
                $stack[] = ['path' => $path, 'token' => $next];
            }
        }

        return $processed;
    }

    protected function applyConflictRules(StorageConnection $connection): void
    {
        $clientId = (int) $connection->client_id;
        $strategy = (string) ($connection->conflict_strategy ?? 'prefer_primary');

        $duplicateNames = SyncedFile::query()
            ->select('synced_files.file_name')
            ->join('storage_connections', 'storage_connections.id', '=', 'synced_files.storage_connection_id')
            ->where('storage_connections.client_id', $clientId)
            ->groupBy('synced_files.file_name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('synced_files.file_name')
            ->all();

        if (empty($duplicateNames)) {
            return;
        }

        $primaryConnectionId = (int) (StorageConnection::query()->where('client_id', $clientId)->where('is_primary', true)->value('id') ?: 0);

        foreach ($duplicateNames as $name) {
            $files = SyncedFile::query()
                ->with('storageConnection')
                ->where('file_name', $name)
                ->whereHas('storageConnection', fn ($q) => $q->where('client_id', $clientId))
                ->get();

            if ($files->count() <= 1) {
                continue;
            }

            $winner = null;
            if ($strategy === 'prefer_primary' && $primaryConnectionId > 0) {
                $winner = $files->firstWhere('storage_connection_id', $primaryConnectionId);
            }
            if (!$winner) {
                $winner = $files->sortByDesc(function (SyncedFile $f) {
                    return $f->last_modified_at?->getTimestamp()
                        ?? $f->synced_at?->getTimestamp()
                        ?? 0;
                })->first();
            }

            foreach ($files as $f) {
                $tags = is_array($f->tags) ? $f->tags : [];
                $tags = array_values(array_filter($tags, fn ($t) => $t !== 'conflict'));

                if ($strategy !== 'keep_both' && $winner && $f->id !== $winner->id) {
                    $tags[] = 'conflict';
                }
                $tags = array_values(array_unique($tags));

                if ($f->tags !== $tags) {
                    $f->update(['tags' => $tags]);
                }
            }
        }
    }
}

