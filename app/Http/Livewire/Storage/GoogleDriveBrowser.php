<?php

namespace App\Http\Livewire\Storage;

use App\Models\Document;
use App\Models\StorageConnection;
use App\Models\SyncedFile;
use App\Services\Storage\GoogleDriveService;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class GoogleDriveBrowser extends Component
{
    use WithFileUploads;

    public ?StorageConnection $connection = null;

    public ?int $connectionId = null;

    public string $folderId = '';

    public string $search = '';

    /** @var array<int, array<string, mixed>> */
    public array $folders = [];

    /** @var array<int, array<string, mixed>> */
    public array $files = [];

    /** @var array<int, mixed> */
    public array $uploads = [];

    public string $newFolderName = '';

    public bool $linkModalOpen = false;

    public string $linkFileId = '';

    public string $linkFileName = '';

    public string $linkFileMime = '';

    public ?int $linkDocumentId = null;

    public function mount(?int $connection = null, ?string $folder = null): void
    {
        $this->connectionId = $connection;
        $this->loadConnection();

        $base = (string) data_get($this->connection->credentials, 'folder_id', '');
        $this->folderId = $folder ?: ($base !== '' ? $base : 'root');

        $this->refreshListing();
    }

    protected function loadConnection(): void
    {
        $user = auth()->user();

        $q = StorageConnection::query()->where('provider', 'google_drive')->where('status', 'connected');
        if ($this->connectionId) {
            $q->where('id', $this->connectionId);
        } else {
            if ($user?->client_id) {
                $q->where('client_id', $user->client_id)->where('is_primary', true);
            }
        }

        $conn = $q->first();
        if (! $conn) {
            abort(404, 'Google Drive connection not found.');
        }

        if ($user && $user->hasRole('staff') && ! $user->hasAnyRole(['super_admin', 'admin'])) {
            $allowed = $user->assignedClientIds();
            if (! in_array((int) $conn->client_id, $allowed, true)) {
                abort(403, 'You do not have access to this client storage.');
            }
        }

        if ($user && $user->isClient() && (int) $conn->client_id !== (int) $user->client_id) {
            abort(403, 'You do not have access to this storage connection.');
        }

        $this->connection = $conn;
        $this->connectionId = $conn->id;
    }

    public function refreshListing(?GoogleDriveService $drive = null): void
    {
        $drive ??= app(GoogleDriveService::class);
        $drive->useConnection($this->connection);

        $folders = $drive->listFolders($this->folderId);
        $files = $drive->listFiles($this->folderId);

        if ($this->search !== '') {
            $s = Str::lower($this->search);
            $folders = collect($folders)->filter(fn ($i) => str_contains(Str::lower((string) ($i['name'] ?? '')), $s))->values()->all();
            $files = collect($files)->filter(fn ($i) => str_contains(Str::lower((string) ($i['name'] ?? '')), $s))->values()->all();
        }

        $this->folders = collect($folders)->sortBy('name')->values()->all();
        $this->files = collect($files)->sortBy('name')->values()->all();
    }

    public function openFolder(string $folderId): void
    {
        $this->folderId = $folderId;
        $this->refreshListing();
    }

    public function goRoot(): void
    {
        $base = (string) data_get($this->connection->credentials, 'folder_id', '');
        $this->folderId = $base !== '' ? $base : 'root';
        $this->refreshListing();
    }

    public function createFolder(GoogleDriveService $drive): void
    {
        $this->validate([
            'newFolderName' => ['required', 'string', 'max:255'],
        ]);

        $drive->useConnection($this->connection)->createFolder($this->newFolderName, $this->folderId);
        $this->newFolderName = '';
        session()->flash('success', 'Folder created.');
        $this->refreshListing($drive);
    }

    public function upload(GoogleDriveService $drive): void
    {
        $this->validate([
            'uploads.*' => ['file', 'max:'.(int) (config('client-portal.max_document_upload_size', 51200))],
        ]);

        $drive->useConnection($this->connection);

        foreach ($this->uploads as $file) {
            $drive->uploadFile($file, $file->getClientOriginalName());
        }

        $this->uploads = [];
        session()->flash('success', 'Files uploaded.');
        $this->refreshListing($drive);
    }

    public function download(string $fileId): void
    {
        $url = route('storage.google-drive.download', ['connection' => $this->connection->id, 'file' => $fileId]);
        $this->dispatch('open-url', url: $url);
    }

    public function export(string $fileId, string $mime): void
    {
        $url = route('storage.google-drive.download', ['connection' => $this->connection->id, 'file' => $fileId, 'export' => $mime]);
        $this->dispatch('open-url', url: $url);
    }

    public function delete(string $fileId, GoogleDriveService $drive): void
    {
        $drive->useConnection($this->connection)->deleteFile($fileId);
        session()->flash('success', 'Deleted.');
        $this->refreshListing($drive);
    }

    public function openLinkModal(string $fileId, string $fileName, string $mime): void
    {
        $this->linkFileId = $fileId;
        $this->linkFileName = $fileName;
        $this->linkFileMime = $mime;
        $this->linkDocumentId = null;
        $this->linkModalOpen = true;
    }

    public function closeLinkModal(): void
    {
        $this->linkModalOpen = false;
        $this->linkFileId = '';
        $this->linkFileName = '';
        $this->linkFileMime = '';
        $this->linkDocumentId = null;
    }

    public function linkToDocument(): void
    {
        $this->validate([
            'linkDocumentId' => ['required', 'integer', 'exists:documents,id'],
        ]);

        $doc = Document::query()->findOrFail($this->linkDocumentId);
        if ((int) $doc->client_id !== (int) $this->connection->client_id) {
            abort(403);
        }

        SyncedFile::query()->updateOrCreate(
            [
                'storage_connection_id' => $this->connection->id,
                'provider_file_id' => $this->linkFileId,
            ],
            [
                'document_id' => $doc->id,
                'file_name' => $this->linkFileName,
                'file_path' => $this->linkFileId,
                'file_size' => 0,
                'mime_type' => $this->linkFileMime ?: null,
                'last_modified_at' => null,
                'synced_at' => now(),
                'sync_status' => 'synced',
            ]
        );

        session()->flash('success', 'Linked to document.');
        $this->closeLinkModal();
    }

    public function render()
    {
        $documents = Document::query()
            ->where('client_id', $this->connection?->client_id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'title']);

        $pendingCount = $this->connection
            ? SyncedFile::query()->where('storage_connection_id', $this->connection->id)->where('sync_status', 'pending')->count()
            : 0;

        return view('livewire.storage.google-drive-browser', [
            'documents' => $documents,
            'pendingCount' => $pendingCount,
            'accountEmail' => (string) data_get($this->connection->credentials, 'account_email', ''),
        ])->layout('layouts.admin', ['title' => 'Google Drive Browser']);
    }
}
