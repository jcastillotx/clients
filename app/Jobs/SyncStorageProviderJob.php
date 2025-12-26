<?php

namespace App\Jobs;

use App\Models\ClientStorageSetting;
use App\Models\StorageConnection;
use App\Models\StorageFile;
use App\Models\StorageSyncConflict;
use App\Models\StorageSyncLog;
use App\Models\User;
use App\Notifications\StorageQuotaWarning;
use App\Notifications\StorageSyncFailed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SyncStorageProviderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $storageConnectionId)
    {
        $this->onQueue('storage-sync');
    }

    public function handle(): void
    {
        $connection = StorageConnection::query()->with('client')->findOrFail($this->storageConnectionId);

        $log = StorageSyncLog::create([
            'storage_connection_id' => $connection->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            if ($connection->status === 'disconnected' || !$connection->disk) {
                throw new \RuntimeException('Storage connection is not configured with an active filesystem disk.');
            }

            $disk = Storage::disk($connection->disk);
            $folders = $connection->foldersToSync();
            $folders = $folders ?: ['.'];

            $paths = [];
            foreach ($folders as $folder) {
                $folder = trim($folder);
                $folder = $folder === '' ? '.' : $folder;
                foreach ($disk->allFiles($folder) as $path) {
                    $paths[] = $path;
                }
            }
            $paths = array_values(array_unique($paths));

            $existing = StorageFile::query()
                ->where('storage_connection_id', $connection->id)
                ->get()
                ->keyBy('path');

            $seen = [];
            $added = 0;
            $updated = 0;

            DB::transaction(function () use ($paths, $disk, $connection, $existing, &$seen, &$added, &$updated) {
                foreach ($paths as $path) {
                    $seen[$path] = true;

                    $filename = basename($path);
                    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION)) ?: null;
                    $size = (int) ($disk->size($path) ?? 0);
                    $mime = null;
                    try {
                        $mime = $disk->mimeType($path);
                    } catch (\Throwable) {
                        // ignore
                    }

                    $modifiedAt = null;
                    try {
                        $ts = $disk->lastModified($path);
                        $modifiedAt = $ts ? now()->setTimestamp($ts) : null;
                    } catch (\Throwable) {
                        // ignore
                    }

                    $row = [
                        'storage_connection_id' => $connection->id,
                        'path' => $path,
                        'filename' => $filename,
                        'extension' => $extension,
                        'mime_type' => $mime,
                        'size_bytes' => $size,
                        'modified_at' => $modifiedAt,
                    ];

                    $existingFile = $existing->get($path);
                    if (!$existingFile) {
                        StorageFile::create($row);
                        $added++;
                    } else {
                        $dirty = false;
                        foreach (['filename', 'extension', 'mime_type', 'size_bytes'] as $k) {
                            if ($existingFile->{$k} !== $row[$k]) {
                                $dirty = true;
                                break;
                            }
                        }
                        if (!$dirty && $modifiedAt && $existingFile->modified_at && $existingFile->modified_at->ne($modifiedAt)) {
                            $dirty = true;
                        }
                        if ($dirty) {
                            $existingFile->update($row);
                            $updated++;
                        }
                    }
                }
            });

            // deletions
            $deleted = StorageFile::query()
                ->where('storage_connection_id', $connection->id)
                ->whereNotIn('path', array_keys($seen))
                ->delete();

            $usedBytes = (int) StorageFile::query()
                ->where('storage_connection_id', $connection->id)
                ->sum('size_bytes');

            $connection->update([
                'used_bytes' => $usedBytes,
                'last_sync_at' => now(),
                'last_error' => null,
                'status' => 'active',
            ]);

            $conflicts = $this->detectConflicts($connection->client_id);

            $log->update([
                'status' => 'success',
                'finished_at' => now(),
                'files_scanned' => count($paths),
                'files_added' => $added,
                'files_updated' => $updated,
                'files_deleted' => (int) $deleted,
                'conflicts' => $conflicts,
            ]);

            $this->maybeNotifyQuota($connection);
        } catch (\Throwable $e) {
            $connection->update([
                'status' => 'error',
                'last_error' => $e->getMessage(),
                'last_sync_at' => now(),
            ]);

            $log->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            $this->notifySyncFailure($connection, $e->getMessage());
            throw $e;
        }
    }

    protected function detectConflicts(int $clientId): int
    {
        $connections = StorageConnection::query()
            ->where('client_id', $clientId)
            ->where('status', 'active')
            ->pluck('id')
            ->all();

        if (count($connections) < 2) {
            return 0;
        }

        $files = StorageFile::query()
            ->whereIn('storage_connection_id', $connections)
            ->select(['id', 'storage_connection_id', 'filename', 'path', 'checksum', 'size_bytes', 'modified_at'])
            ->get()
            ->groupBy('filename');

        $settings = ClientStorageSetting::query()->firstOrCreate(['client_id' => $clientId]);
        $rule = (string) $settings->conflict_rule;

        $primaryId = (int) (StorageConnection::query()
            ->where('client_id', $clientId)
            ->where('is_primary', true)
            ->value('id') ?? 0);

        $conflicts = 0;

        foreach ($files as $filename => $rows) {
            if ($rows->count() < 2) {
                continue;
            }

            $fingerprints = $rows->map(fn ($r) => ($r->checksum ?: 'nochk') . ':' . $r->size_bytes)->unique();
            if ($fingerprints->count() <= 1) {
                continue;
            }

            $candidates = $rows->map(fn ($r) => [
                'connection_id' => $r->storage_connection_id,
                'path' => $r->path,
                'checksum' => $r->checksum,
                'size_bytes' => $r->size_bytes,
                'modified_at' => optional($r->modified_at)->toDateTimeString(),
            ])->values()->all();

            $chosen = null;
            $resolution = 'unresolved';
            $notes = null;

            if ($rule === 'prefer_primary') {
                $chosen = collect($candidates)->firstWhere('connection_id', $primaryId) ?? null;
                if (!$chosen) {
                    $chosen = collect($candidates)->sortByDesc('modified_at')->first();
                    $notes = 'Primary not present for this filename; chose newest.';
                }
                $resolution = 'prefer_primary';
            } elseif ($rule === 'prefer_newest') {
                $chosen = collect($candidates)->sortByDesc('modified_at')->first();
                $resolution = 'prefer_newest';
            } elseif ($rule === 'keep_both') {
                $resolution = 'kept_both';
                $chosen = null;
            }

            StorageSyncConflict::updateOrCreate(
                ['client_id' => $clientId, 'filename' => (string) $filename],
                [
                    'candidates' => $candidates,
                    'chosen' => $chosen,
                    'resolution' => $resolution,
                    'notes' => $notes,
                ]
            );
            $conflicts++;
        }

        return $conflicts;
    }

    protected function maybeNotifyQuota(StorageConnection $connection): void
    {
        $quotaBytes = $connection->quota_bytes;
        if (!$quotaBytes || $quotaBytes <= 0) {
            return;
        }

        $settings = ClientStorageSetting::query()->firstOrCreate(['client_id' => $connection->client_id]);
        $threshold = max(1, min(100, (int) $settings->quota_alert_percent));

        $percent = (int) floor(($connection->used_bytes / $quotaBytes) * 100);
        if ($percent < $threshold) {
            return;
        }

        $lastNotified = Arr::get($connection->settings, 'last_quota_notified_at');
        if ($lastNotified) {
            try {
                $last = now()->parse($lastNotified);
                if ($last->diffInHours(now()) < 24) {
                    return;
                }
            } catch (\Throwable) {
                // ignore parse error
            }
        }

        $users = User::query()->where('client_id', $connection->client_id)->get();
        Notification::send($users, new StorageQuotaWarning($connection, $percent));

        $connection->update([
            'settings' => array_merge((array) $connection->settings, [
                'last_quota_notified_at' => now()->toDateTimeString(),
            ]),
        ]);
    }

    protected function notifySyncFailure(StorageConnection $connection, string $message): void
    {
        $users = User::query()->where('client_id', $connection->client_id)->get();
        Notification::send($users, new StorageSyncFailed($connection, Str::limit($message, 240)));
    }
}

