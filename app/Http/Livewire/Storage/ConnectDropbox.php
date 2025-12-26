<?php

namespace App\Http\Livewire\Storage;

use App\Models\Client;
use App\Models\StorageConnection;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ConnectDropbox extends Component
{
    public ?int $client_id = null;
    public string $folder_path = '';
    public bool $is_primary = true;

    public ?int $connection_id = null;
    public string $account_email = '';
    public string $status = '';

    public function mount(): void
    {
        $user = auth()->user();
        if ($user?->client_id) {
            $this->client_id = $user->client_id;
        }

        $this->refreshConnection();
    }

    protected function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')],
            'folder_path' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['boolean'],
        ];
    }

    public function updated($property): void
    {
        $this->validateOnly($property);
    }

    public function refreshConnection(): void
    {
        $this->connection_id = null;
        $this->account_email = '';
        $this->status = '';

        if (!$this->client_id) {
            return;
        }

        $conn = StorageConnection::query()
            ->where('client_id', $this->client_id)
            ->where('provider', 'dropbox')
            ->first();

        if (!$conn) {
            return;
        }

        $this->connection_id = $conn->id;
        $this->status = (string) $conn->status;
        $this->account_email = (string) data_get($conn->credentials, 'account_email', '');
        $this->folder_path = (string) data_get($conn->credentials, 'folder_path', $this->folder_path);
        $this->is_primary = (bool) $conn->is_primary;
    }

    public function saveSettings(): void
    {
        $data = $this->validate();

        $conn = StorageConnection::query()
            ->where('client_id', $data['client_id'])
            ->where('provider', 'dropbox')
            ->first();

        if (!$conn) {
            session()->flash('error', 'Connect Dropbox first, then configure folder/primary settings.');
            return;
        }

        $creds = (array) ($conn->credentials ?? []);
        $creds['folder_path'] = trim((string) ($data['folder_path'] ?? ''), '/');

        $conn->update([
            'credentials' => $creds,
            'is_primary' => (bool) $data['is_primary'],
            'status' => 'connected',
        ]);

        if ((bool) $data['is_primary']) {
            StorageConnection::query()
                ->where('client_id', $data['client_id'])
                ->where('id', '!=', $conn->id)
                ->update(['is_primary' => false]);
        }

        $this->refreshConnection();
        session()->flash('success', 'Dropbox settings saved.');
    }

    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user?->isAdmin() || $user?->isStaff();

        $clients = $isAdmin
            ? Client::query()->orderBy('company_name')->get(['id', 'company_name'])
            : collect();

        return view('livewire.storage.connect-dropbox', [
            'isAdmin' => $isAdmin,
            'clients' => $clients,
            'authorizeUrl' => route('storage.dropbox.authorize', [
                'client_id' => $this->client_id,
                'folder_path' => $this->folder_path,
                'is_primary' => $this->is_primary ? 1 : 0,
                'redirect_to' => route('admin.storage.dropbox.connect'),
            ]),
        ])->layout('layouts.admin', ['title' => 'Connect Dropbox']);
    }
}

