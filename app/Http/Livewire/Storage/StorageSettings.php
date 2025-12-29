<?php

namespace App\Http\Livewire\Storage;

use App\Models\ClientStorageSetting;
use App\Models\StorageConnection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class StorageSettings extends Component
{
    public ?int $clientId = null;

    public bool $auto_sync_enabled = true;

    public string $auto_sync_frequency = 'daily';

    public string $conflict_rule = 'prefer_primary';

    public int $quota_alert_percent = 80;

    public bool $backup_enabled = false;

    public ?int $backup_connection_id = null;

    public string $folders_csv = '';

    public ?int $primary_connection_id = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        // Admin/staff should use admin storage
        if (! $user->isClient()) {
            if ($user->can('access admin panel')) {
                redirect()->route('admin.storage')->send();

                return;
            }
            abort(403);
        }

        // Client must have a client_id associated
        if (! $user->client_id) {
            abort(403, 'No client associated with this account. Please contact support.');
        }

        $this->clientId = $user->client_id;

        $settings = ClientStorageSetting::query()->firstOrCreate(['client_id' => $this->clientId]);
        $this->auto_sync_enabled = (bool) $settings->auto_sync_enabled;
        $this->auto_sync_frequency = (string) $settings->auto_sync_frequency;
        $this->conflict_rule = (string) $settings->conflict_rule;
        $this->quota_alert_percent = (int) $settings->quota_alert_percent;
        $this->backup_enabled = (bool) $settings->backup_enabled;
        $this->backup_connection_id = $settings->backup_connection_id;
        $this->folders_csv = implode(', ', (array) ($settings->folders ?? []));

        $primary = StorageConnection::query()
            ->where('client_id', $this->clientId)
            ->where('is_primary', true)
            ->first();
        $this->primary_connection_id = $primary?->id;
    }

    public function save(): void
    {
        Validator::make([
            'auto_sync_enabled' => $this->auto_sync_enabled,
            'auto_sync_frequency' => $this->auto_sync_frequency,
            'conflict_rule' => $this->conflict_rule,
            'quota_alert_percent' => $this->quota_alert_percent,
            'backup_enabled' => $this->backup_enabled,
            'backup_connection_id' => $this->backup_connection_id,
        ], [
            'auto_sync_enabled' => ['boolean'],
            'auto_sync_frequency' => ['required', 'in:hourly,daily,weekly'],
            'conflict_rule' => ['required', 'in:prefer_primary,prefer_newest,keep_both'],
            'quota_alert_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'backup_enabled' => ['boolean'],
            'backup_connection_id' => ['nullable', 'integer'],
        ])->validate();

        $folders = collect(explode(',', (string) $this->folders_csv))
            ->map(fn ($f) => trim($f))
            ->filter()
            ->values()
            ->all();

        ClientStorageSetting::query()->updateOrCreate(
            ['client_id' => $this->clientId],
            [
                'auto_sync_enabled' => $this->auto_sync_enabled,
                'auto_sync_frequency' => $this->auto_sync_frequency,
                'conflict_rule' => $this->conflict_rule,
                'quota_alert_percent' => $this->quota_alert_percent,
                'backup_enabled' => $this->backup_enabled,
                'backup_connection_id' => $this->backup_enabled ? $this->backup_connection_id : null,
                'folders' => $folders,
            ]
        );

        // Primary provider selection
        if ($this->primary_connection_id) {
            StorageConnection::query()
                ->where('client_id', $this->clientId)
                ->update(['is_primary' => false]);

            StorageConnection::query()
                ->where('client_id', $this->clientId)
                ->where('id', $this->primary_connection_id)
                ->update(['is_primary' => true]);
        }

        session()->flash('success', 'Storage settings saved.');
    }

    public function render()
    {
        $connections = StorageConnection::query()
            ->where('client_id', $this->clientId)
            ->where('status', 'active')
            ->orderByDesc('is_primary')
            ->get();

        return view('livewire.storage.settings', [
            'connections' => $connections,
        ]);
    }
}
