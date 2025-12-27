<?php

namespace App\Services\Storage;

use App\Jobs\SyncStorageProviderJob;
use App\Models\ClientStorageSetting;
use App\Models\StorageConnection;

class StorageSyncScheduler
{
    public function dispatchDue(): int
    {
        $connections = StorageConnection::query()
            ->where('status', 'active')
            ->whereNotNull('disk')
            ->get();

        $count = 0;

        foreach ($connections as $connection) {
            $settings = ClientStorageSetting::query()->firstOrCreate(['client_id' => $connection->client_id]);
            if (! $settings->auto_sync_enabled) {
                continue;
            }

            $due = match ($settings->auto_sync_frequency) {
                'hourly' => $connection->last_sync_at === null || $connection->last_sync_at->lte(now()->subHour()),
                'weekly' => $connection->last_sync_at === null || $connection->last_sync_at->lte(now()->subWeek()),
                default => $connection->last_sync_at === null || $connection->last_sync_at->lte(now()->subDay()),
            };

            if (! $due) {
                continue;
            }

            SyncStorageProviderJob::dispatch($connection->id);
            $count++;
        }

        return $count;
    }
}
