<?php

namespace App\Http\Livewire\Storage;

use App\Jobs\SyncStorageProviderJob;
use App\Models\ClientStorageSetting;
use App\Models\StorageConnection;
use App\Models\StorageSyncConflict;
use App\Models\StorageSyncLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class StorageDashboard extends Component
{
    public ?int $clientId = null;

    // connect new
    public string $new_provider = 's3';
    public string $new_name = '';
    public string $new_disk = '';
    public ?int $new_quota_gb = null;

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user?->client_id, 403);
        $this->clientId = $user->client_id;
    }

    public function connectNew(): void
    {
        Validator::make([
            'provider' => $this->new_provider,
            'name' => $this->new_name,
            'disk' => $this->new_disk,
            'quota_gb' => $this->new_quota_gb,
        ], [
            'provider' => ['required', 'in:s3,dropbox,drive'],
            'name' => ['required', 'string', 'max:120'],
            'disk' => ['required', 'string', 'max:120'],
            'quota_gb' => ['nullable', 'integer', 'min:1', 'max:1048576'],
        ])->validate();

        $quotaBytes = $this->new_quota_gb ? (int) $this->new_quota_gb * 1024 * 1024 * 1024 : null;

        $isFirst = !StorageConnection::query()->where('client_id', $this->clientId)->exists();

        StorageConnection::create([
            'client_id' => $this->clientId,
            'provider' => $this->new_provider,
            'name' => $this->new_name,
            'disk' => $this->new_disk,
            'status' => 'active',
            'is_primary' => $isFirst,
            'quota_bytes' => $quotaBytes,
            'settings' => [
                'folders' => ['.'],
            ],
        ]);

        $this->reset(['new_name', 'new_disk', 'new_quota_gb']);
        session()->flash('success', 'Storage provider connected.');
    }

    public function syncNow(int $connectionId): void
    {
        $c = $this->connectionOrFail($connectionId);
        SyncStorageProviderJob::dispatch($c->id);
        session()->flash('success', "Sync queued for {$c->name}.");
    }

    public function disconnect(int $connectionId): void
    {
        $c = $this->connectionOrFail($connectionId);
        $wasPrimary = $c->is_primary;

        $c->update([
            'status' => 'disconnected',
            'is_primary' => false,
            'disk' => null,
        ]);

        if ($wasPrimary) {
            $next = StorageConnection::query()
                ->where('client_id', $this->clientId)
                ->where('status', 'active')
                ->orderByDesc('id')
                ->first();
            if ($next) {
                $next->update(['is_primary' => true]);
            }
        }

        session()->flash('success', 'Provider disconnected.');
    }

    protected function connectionOrFail(int $id): StorageConnection
    {
        $c = StorageConnection::query()->findOrFail($id);
        abort_unless($c->client_id === $this->clientId, 403);
        return $c;
    }

    public function render()
    {
        $connections = StorageConnection::query()
            ->where('client_id', $this->clientId)
            ->orderByDesc('is_primary')
            ->orderBy('provider')
            ->get();

        $settings = ClientStorageSetting::query()->firstOrCreate(['client_id' => $this->clientId]);

        $recentSyncLogs = StorageSyncLog::query()
            ->whereIn('storage_connection_id', $connections->pluck('id'))
            ->latest('id')
            ->limit(15)
            ->get();

        $conflicts = StorageSyncConflict::query()
            ->where('client_id', $this->clientId)
            ->where('resolution', 'unresolved')
            ->latest('id')
            ->limit(10)
            ->get();

        $stats = [
            'providers' => $connections->count(),
            'active' => $connections->where('status', 'active')->count(),
            'used_bytes' => (int) $connections->sum('used_bytes'),
            'quota_bytes' => (int) $connections->whereNotNull('quota_bytes')->sum('quota_bytes'),
            'last_sync_at' => StorageSyncLog::query()
                ->whereIn('storage_connection_id', $connections->pluck('id'))
                ->where('status', 'success')
                ->max('finished_at'),
            'unresolved_conflicts' => (int) StorageSyncConflict::where('client_id', $this->clientId)->where('resolution', 'unresolved')->count(),
        ];

        $primary = $connections->firstWhere('is_primary', true);

        return view('livewire.storage.dashboard', compact('connections', 'stats', 'primary', 'settings', 'recentSyncLogs', 'conflicts'));
    }
}

