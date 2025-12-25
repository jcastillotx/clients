<?php

namespace App\Http\Livewire\Storage;

use App\Models\Document;
use App\Models\StorageConnection;
use App\Models\SyncedFile;
use App\Services\Storage\DropboxService;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class DropboxBrowser extends Component
{
    use WithFileUploads;

    public ?StorageConnection $connection = null;
    public ?int $connectionId = null;
    public string $path = '';
    public string $search = '';

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    /** @var array<int, mixed> */
    public array $uploads = [];

    public string $newFolderName = '';

    // Link-to-document modal
    public bool $linkModalOpen = false;
    public string $linkFilePath = '';
    public string $linkFileId = '';
    public string $linkFileName = '';
    public ?int $linkDocumentId = null;

    public function mount(?int $connection = null, ?string $path = null): void
    {
        $this->connectionId = $connection;
        $this->path = $path ? trim($path, '/') : '';
        $this->loadConnection();
        $this->refreshListing();
    }

    protected function loadConnection(): void
    {
        $user = auth()->user();

        $q = StorageConnection::query()->where('provider', 'dropbox')->where('status', 'connected');

        if ($this->connectionId) {
            $q->where('id', $this->connectionId);
        } else {
            if ($user?->client_id) {
                $q->where('client_id', $user->client_id)->where('is_primary', true);
            }
        }

        $conn = $q->first();
        if (!$conn) {
            abort(404, 'Dropbox connection not found.');
        }

        // Staff scoping: staff can only browse assigned clients.
        if ($user && $user->hasRole('staff') && !$user->hasAnyRole(['super_admin', 'admin'])) {
            $allowed = $user->assignedClientIds();
            if (!in_array((int) $conn->client_id, $allowed, true)) {
                abort(403, 'You do not have access to this client storage.');
            }
        }

        // Clients can only browse their own.
        if ($user && $user->isClient() && (int) $conn->client_id !== (int) $user->client_id) {
            abort(403, 'You do not have access to this storage connection.');
        }

        $this->connection = $conn;
        $this->connectionId = $conn->id;
    }

    public function refreshListing(DropboxService $dropbox = null): void
    {
        $dropbox ??= app(DropboxService::class);
        $dropbox->useConnection($this->connection);

        $items = $dropbox->listFiles($this->path);

        if ($this->search !== '') {
            $s = Str::lower($this->search);
            $items = collect($items)->filter(fn ($i) => str_contains(Str::lower((string) ($i['name'] ?? '')), $s))->values()->all();
        }

        // Sort folders first, then files.
        $this->items = collect($items)->sortBy(function ($i) {
            return (($i['type'] ?? '') === 'folder') ? '0_' . ($i['name'] ?? '') : '1_' . ($i['name'] ?? '');
        })->values()->all();
    }

    public function openFolder(string $folderPath): void
    {
        $folderPath = trim($folderPath, '/');
        $this->path = trim(($this->path ? $this->path . '/' : '') . $folderPath, '/');
        $this->refreshListing();
    }

    public function goUp(): void
    {
        if ($this->path === '') {
            return;
        }
        $parts = explode('/', $this->path);
        array_pop($parts);
        $this->path = implode('/', $parts);
        $this->refreshListing();
    }

    public function createFolder(DropboxService $dropbox): void
    {
        $this->validate([
            'newFolderName' => ['required', 'string', 'max:255'],
        ]);

        $dropbox->useConnection($this->connection)->createFolder($this->newFolderName, $this->path);
        $this->newFolderName = '';
        session()->flash('success', 'Folder created.');
        $this->refreshListing($dropbox);
    }

    public function upload(DropboxService $dropbox): void
    {
        $this->validate([
            'uploads.*' => ['file', 'max:' . (int) (config('client-portal.max_document_upload_size', 51200))],
        ]);

        $dropbox->useConnection($this->connection);

        foreach ($this->uploads as $file) {
            $name = $file->getClientOriginalName();
            $target = trim(($this->path ? $this->path . '/' : '') . $name, '/');
            $dropbox->uploadFile($file, $target);
        }

        $this->uploads = [];
        session()->flash('success', 'Files uploaded.');
        $this->refreshListing($dropbox);
    }

    public function download(string $path): void
    {
        $dropbox = app(DropboxService::class)->useConnection($this->connection);
        $url = $dropbox->downloadFile($path);
        $this->dispatch('open-url', url: $url);
    }

    public function delete(string $path, DropboxService $dropbox): void
    {
        $dropbox->useConnection($this->connection)->deleteFile($path);
        session()->flash('success', 'Deleted.');
        $this->refreshListing($dropbox);
    }

    public function openLinkModal(string $fileId, string $filePath, string $fileName): void
    {
        $this->linkFileId = $fileId;
        $this->linkFilePath = $filePath;
        $this->linkFileName = $fileName;
        $this->linkDocumentId = null;
        $this->linkModalOpen = true;
    }

    public function closeLinkModal(): void
    {
        $this->linkModalOpen = false;
        $this->linkFileId = '';
        $this->linkFilePath = '';
        $this->linkFileName = '';
        $this->linkDocumentId = null;
    }

    public function linkToDocument(): void
    {
        $this->validate([
            'linkDocumentId' => ['required', 'integer', 'exists:documents,id'],
        ]);

        $doc = Document::query()->findOrFail($this->linkDocumentId);

        // Ensure the document belongs to the same client.
        if ((int) $doc->client_id !== (int) $this->connection->client_id) {
            abort(403);
        }

        SyncedFile::query()->updateOrCreate(
            [
                'storage_connection_id' => $this->connection->id,
                'provider_file_id' => $this->linkFileId ?: $this->linkFilePath,
            ],
            [
                'document_id' => $doc->id,
                'file_name' => $this->linkFileName,
                'file_path' => $this->linkFilePath,
                'file_size' => 0,
                'mime_type' => null,
                'last_modified_at' => null,
                'synced_at' => now(),
                'sync_status' => 'synced',
            ]
        );

        session()->flash('success', 'Linked to document.');
        $this->closeLinkModal();
    }

    public function syncNow(DropboxService $dropbox): void
    {
        $dropbox->useConnection($this->connection)->syncChanges((int) config('storage-providers.sync.max_files_per_run', 500));
        session()->flash('success', 'Sync queued/applied.');
        $this->loadConnection();
        $this->refreshListing($dropbox);
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

        return view('livewire.storage.dropbox-browser', [
            'documents' => $documents,
            'pendingCount' => $pendingCount,
        ])->layout('layouts.admin', ['title' => 'Dropbox Browser']);
    }
}

