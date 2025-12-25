<?php

namespace App\Http\Livewire\Storage;

use App\Models\Client;
use App\Models\StorageConnection;
use Illuminate\Validation\Rule;
use Livewire\Component;

class StorageSettings extends Component
{
    public ?int $client_id = null;

    /** @var array<int, array<string, mixed>> */
    public array $connections = [];

    public function mount(): void
    {
        $user = auth()->user();
        if ($user?->client_id) {
            $this->client_id = (int) $user->client_id;
        }

        $this->loadConnections();
    }

    public function updatedClientId(): void
    {
        $this->loadConnections();
    }

    public function loadConnections(): void
    {
        $user = auth()->user();
        $q = StorageConnection::query()->orderBy('provider');

        if ($user?->isClient()) {
            $q->where('client_id', $user->client_id);
        } elseif ($user && $user->hasRole('staff') && !$user->hasAnyRole(['super_admin', 'admin'])) {
            $q->whereIn('client_id', $user->assignedClientIds());
        } elseif ($this->client_id) {
            $q->where('client_id', $this->client_id);
        }

        $this->connections = $q->get()->map(function (StorageConnection $c) {
            $creds = (array) ($c->credentials ?? []);
            return [
                'id' => $c->id,
                'client_id' => (int) $c->client_id,
                'provider' => (string) $c->provider,
                'status' => (string) $c->status,
                'is_primary' => (bool) $c->is_primary,
                'auto_sync_enabled' => (bool) ($c->auto_sync_enabled ?? true),
                'sync_frequency_minutes' => (int) ($c->sync_frequency_minutes ?? 0),
                'conflict_strategy' => (string) ($c->conflict_strategy ?? 'prefer_primary'),
                'quota_alerts_enabled' => (bool) ($creds['quota_alerts_enabled'] ?? true),
                'sync_failure_alerts_enabled' => (bool) ($creds['sync_failure_alerts_enabled'] ?? true),
                'folder_path' => (string) ($creds['folder_path'] ?? ''),
                'bucket' => (string) ($creds['bucket'] ?? ''),
                'drive_folder_id' => (string) ($creds['folder_id'] ?? ''),
            ];
        })->all();
    }

    public function save(int $connectionId): void
    {
        $conn = StorageConnection::query()->findOrFail($connectionId);
        $this->authorizeConnection($conn);

        $row = collect($this->connections)->firstWhere('id', $connectionId);
        if (!$row) {
            return;
        }

        $data = validator($row, [
            'auto_sync_enabled' => ['boolean'],
            'sync_frequency_minutes' => ['integer', 'min:0', 'max:10080'],
            'conflict_strategy' => ['required', Rule::in(['prefer_primary', 'prefer_newest', 'keep_both'])],
            'quota_alerts_enabled' => ['boolean'],
            'sync_failure_alerts_enabled' => ['boolean'],
            'folder_path' => ['nullable', 'string', 'max:255'],
            'drive_folder_id' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['boolean'],
        ])->validate();

        $creds = (array) ($conn->credentials ?? []);
        $creds['quota_alerts_enabled'] = (bool) ($data['quota_alerts_enabled'] ?? true);
        $creds['sync_failure_alerts_enabled'] = (bool) ($data['sync_failure_alerts_enabled'] ?? true);
        if ($conn->provider === 'aws_s3' || $conn->provider === 'dropbox') {
            $creds['folder_path'] = trim((string) ($data['folder_path'] ?? ''), '/');
        }
        if ($conn->provider === 'google_drive') {
            $creds['folder_id'] = trim((string) ($data['drive_folder_id'] ?? ''));
        }

        $conn->update([
            'auto_sync_enabled' => (bool) ($data['auto_sync_enabled'] ?? true),
            'sync_frequency_minutes' => (int) ($data['sync_frequency_minutes'] ?? 0) ?: null,
            'conflict_strategy' => (string) $data['conflict_strategy'],
            'credentials' => $creds,
        ]);

        if (!empty($data['is_primary'])) {
            StorageConnection::query()->where('client_id', $conn->client_id)->update(['is_primary' => false]);
            $conn->update(['is_primary' => true]);
        }

        session()->flash('success', 'Storage settings saved.');
        $this->loadConnections();
    }

    protected function authorizeConnection(StorageConnection $conn): void
    {
        $user = auth()->user();
        if (!$user) abort(403);

        if ($user->isClient()) {
            abort_if((int) $user->client_id !== (int) $conn->client_id, 403);
            return;
        }

        if ($user->hasRole('staff') && !$user->hasAnyRole(['super_admin', 'admin'])) {
            abort_if(!in_array((int) $conn->client_id, $user->assignedClientIds(), true), 403);
        }
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

        return view('livewire.storage.settings', [
            'isAdmin' => $isAdmin,
            'clients' => $clients,
            'defaultFreq' => (int) config('storage-providers.sync.frequency_minutes', 15),
        ])->layout('layouts.admin', ['title' => 'Storage Settings']);
    }
}

