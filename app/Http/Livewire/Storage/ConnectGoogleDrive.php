<?php

namespace App\Http\Livewire\Storage;

use App\Models\Client;
use App\Models\StorageConnection;
use App\Services\Storage\GoogleDriveService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ConnectGoogleDrive extends Component
{
    public ?int $client_id = null;

    public string $folder_id = '';

    public string $sync_mode = 'bidirectional'; // bidirectional|upload_only|download_only

    public bool $is_primary = true;

    public ?int $connection_id = null;

    public string $account_email = '';

    public string $status = '';

    public string $share_folder_with = ''; // comma-separated emails

    public string $share_role = 'writer'; // writer|reader

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
            'folder_id' => ['nullable', 'string', 'max:255'],
            'sync_mode' => ['required', Rule::in(['bidirectional', 'upload_only', 'download_only'])],
            'is_primary' => ['boolean'],
            'share_folder_with' => ['nullable', 'string', 'max:1000'],
            'share_role' => ['required', Rule::in(['writer', 'reader'])],
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

        if (! $this->client_id) {
            return;
        }

        $conn = StorageConnection::query()
            ->where('client_id', $this->client_id)
            ->where('provider', 'google_drive')
            ->first();

        if (! $conn) {
            return;
        }

        $this->connection_id = $conn->id;
        $this->status = (string) $conn->status;
        $this->account_email = (string) data_get($conn->credentials, 'account_email', '');
        $this->folder_id = (string) data_get($conn->credentials, 'folder_id', $this->folder_id);
        $this->sync_mode = (string) data_get($conn->credentials, 'sync_mode', $this->sync_mode);
        $this->is_primary = (bool) $conn->is_primary;
    }

    public function saveSettings(GoogleDriveService $drive): void
    {
        $data = $this->validate();

        $conn = StorageConnection::query()
            ->where('client_id', $data['client_id'])
            ->where('provider', 'google_drive')
            ->first();

        if (! $conn) {
            session()->flash('error', 'Connect Google Drive first, then configure settings.');

            return;
        }

        $creds = (array) ($conn->credentials ?? []);
        $creds['folder_id'] = trim((string) ($data['folder_id'] ?? ''));
        $creds['sync_mode'] = (string) $data['sync_mode'];

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

        // Optional permission management: share selected folder with team emails.
        $folderId = (string) ($creds['folder_id'] ?? '');
        if ($folderId !== '' && trim((string) ($data['share_folder_with'] ?? '')) !== '') {
            $emails = collect(explode(',', (string) $data['share_folder_with']))
                ->map(fn ($e) => trim($e))
                ->filter(fn ($e) => $e !== '' && Str::contains($e, '@'))
                ->unique()
                ->values()
                ->all();

            try {
                $drive->useConnection($conn);
                foreach ($emails as $email) {
                    $drive->shareFolderWithUser($folderId, $email, (string) $data['share_role']);
                }
                session()->flash('success', 'Settings saved and folder sharing updated.');
            } catch (\Throwable $e) {
                session()->flash('error', 'Settings saved, but sharing failed: '.$e->getMessage());
            }
        } else {
            session()->flash('success', 'Google Drive settings saved.');
        }

        $this->refreshConnection();
    }

    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user?->isAdmin() || $user?->isStaff();

        $clients = $isAdmin
            ? Client::query()->orderBy('company_name')->get(['id', 'company_name'])
            : collect();

        $authorizeUrl = route('storage.google-drive.authorize', [
            'client_id' => $this->client_id,
            'folder_id' => $this->folder_id,
            'sync_mode' => $this->sync_mode,
            'is_primary' => $this->is_primary ? 1 : 0,
            'redirect_to' => route('admin.storage.google-drive.connect'),
        ]);

        return view('livewire.storage.connect-google-drive', [
            'isAdmin' => $isAdmin,
            'clients' => $clients,
            'authorizeUrl' => $authorizeUrl,
        ])->layout('layouts.admin', ['title' => 'Connect Google Drive']);
    }
}
