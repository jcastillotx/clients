<?php

namespace App\Http\Livewire\Storage;

use App\Jobs\SyncStorageConnection;
use App\Models\Client;
use App\Models\StorageConnection;
use App\Models\StorageSyncLog;
use App\Services\Storage\AwsS3Service;
use App\Services\Storage\DropboxService;
use App\Services\Storage\GoogleDriveService;
use Livewire\Component;

class StorageDashboard extends Component
{
    public ?int $client_id = null;

    /** @var array<int, array<string, mixed>> */
    public array $cards = [];

    /** @var array<int, array<string, mixed>> */
    public array $recentLogs = [];

    /** @var array<string, mixed> */
    public array $totals = [
        'used' => 0,
        'total' => null,
        'connections' => 0,
        'connected' => 0,
        'errors' => 0,
    ];

    public function mount(): void
    {
        $user = auth()->user();
        if ($user?->client_id) {
            $this->client_id = (int) $user->client_id;
        }

        $this->refresh();
    }

    public function updatedClientId(): void
    {
        $this->refresh();
    }

    public function refresh(): void
    {
        $user = auth()->user();
        $isAdmin = $user?->isAdmin() || $user?->isStaff();

        $q = StorageConnection::query()->with('client')->orderBy('provider');

        if (!$isAdmin) {
            $q->where('client_id', $user?->client_id);
        } else {
            if ($this->client_id) {
                $q->where('client_id', $this->client_id);
            } else {
                // Admin default: show all clients aggregated.
            }

            // Staff scoping.
            if ($user && $user->hasRole('staff') && !$user->hasAnyRole(['super_admin', 'admin'])) {
                $allowed = $user->assignedClientIds();
                $q->whereIn('client_id', $allowed);
            }
        }

        $connections = $q->get();

        $usedSum = 0;
        $totalSum = 0;
        $totalKnown = true;

        $cards = [];
        $connectionIds = [];
        foreach ($connections as $conn) {
            $connectionIds[] = $conn->id;
            $used = (int) ($conn->storage_used ?? 0);
            $limit = $conn->storage_limit !== null ? (int) $conn->storage_limit : null;

            $usedSum += $used;
            if ($limit === null) {
                $totalKnown = false;
            } else {
                $totalSum += $limit;
            }

            $lastLog = StorageSyncLog::query()
                ->where('storage_connection_id', $conn->id)
                ->orderByDesc('started_at')
                ->first();

            $cards[] = [
                'id' => $conn->id,
                'client_id' => (int) $conn->client_id,
                'client_name' => $conn->client?->company_name,
                'provider' => (string) $conn->provider,
                'status' => (string) $conn->status,
                'is_primary' => (bool) $conn->is_primary,
                'used' => $used,
                'total' => $limit,
                'last_synced_at' => $conn->last_synced_at,
                'auto_sync_enabled' => (bool) ($conn->auto_sync_enabled ?? true),
                'conflict_strategy' => (string) ($conn->conflict_strategy ?? 'prefer_primary'),
                'last_sync_status' => $lastLog?->status,
                'last_sync_message' => $lastLog?->message,
                'last_sync_started_at' => $lastLog?->started_at,
            ];
        }

        $this->cards = $cards;
        $this->totals = [
            'used' => $usedSum,
            'total' => $totalKnown ? $totalSum : null,
            'connections' => $connections->count(),
            'connected' => $connections->where('status', 'connected')->count(),
            'errors' => $connections->where('status', 'error')->count(),
        ];

        $this->recentLogs = empty($connectionIds)
            ? []
            : StorageSyncLog::query()
                ->with('storageConnection.client')
                ->whereIn('storage_connection_id', $connectionIds)
                ->orderByDesc('started_at')
                ->limit(15)
                ->get()
                ->map(fn (StorageSyncLog $l) => [
                    'id' => $l->id,
                    'client' => $l->storageConnection?->client?->company_name,
                    'provider' => $l->storageConnection?->provider,
                    'status' => $l->status,
                    'files_processed' => (int) ($l->files_processed ?? 0),
                    'started_at' => $l->started_at,
                    'finished_at' => $l->finished_at,
                    'message' => $l->message,
                ])
                ->all();
    }

    public function syncNow(int $connectionId): void
    {
        $conn = $this->findScopedConnection($connectionId);
        if (!$conn) {
            return;
        }

        SyncStorageConnection::dispatch($conn->id, (int) config('storage-providers.sync.max_files_per_run', 500))->onQueue('default');
        session()->flash('success', 'Sync queued.');
        $this->refresh();
    }

    public function disconnect(int $connectionId): void
    {
        $conn = $this->findScopedConnection($connectionId);
        if (!$conn) {
            return;
        }

        match ($conn->provider) {
            'aws_s3' => app(AwsS3Service::class)->useConnection($conn)->disconnect(),
            'dropbox' => app(DropboxService::class)->useConnection($conn)->disconnect(),
            'google_drive' => app(GoogleDriveService::class)->useConnection($conn)->disconnect(),
            default => null,
        };

        session()->flash('success', 'Disconnected.');
        $this->refresh();
    }

    public function setPrimary(int $connectionId): void
    {
        $conn = $this->findScopedConnection($connectionId);
        if (!$conn) {
            return;
        }

        StorageConnection::query()
            ->where('client_id', $conn->client_id)
            ->update(['is_primary' => false]);

        $conn->update(['is_primary' => true]);

        session()->flash('success', 'Primary storage updated.');
        $this->refresh();
    }

    protected function findScopedConnection(int $id): ?StorageConnection
    {
        $user = auth()->user();
        $q = StorageConnection::query()->where('id', $id);

        if ($user?->isClient()) {
            $q->where('client_id', $user->client_id);
        } elseif ($user && $user->hasRole('staff') && !$user->hasAnyRole(['super_admin', 'admin'])) {
            $q->whereIn('client_id', $user->assignedClientIds());
        }

        return $q->first();
    }

    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user?->isAdmin() || $user?->isStaff();

        $clients = collect();
        if ($isAdmin) {
            $q = Client::query()->orderBy('company_name');
            if ($user && $user->hasRole('staff') && !$user->hasAnyRole(['super_admin', 'admin'])) {
                $q->whereIn('id', $user->assignedClientIds());
            }
            $clients = $q->get(['id', 'company_name']);
        }

        return view('livewire.storage.dashboard', [
            'isAdmin' => $isAdmin,
            'clients' => $clients,
        ])->layout('layouts.admin', ['title' => 'Storage']);
    }
}

