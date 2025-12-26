<?php

namespace App\Jobs;

use App\Models\StorageConnection;
use App\Services\Storage\DropboxService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncDropboxChanges implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $connectionId) {}

    public function handle(DropboxService $service): void
    {
        $connection = StorageConnection::query()->find($this->connectionId);
        if (!$connection || $connection->provider !== 'dropbox') {
            return;
        }

        // Only sync connected integrations.
        if ($connection->status !== 'connected') {
            return;
        }

        $service->useConnection($connection)->syncChanges((int) config('storage-providers.sync.max_files_per_run', 500));
    }
}

