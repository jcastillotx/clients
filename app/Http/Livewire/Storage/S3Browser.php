<?php

namespace App\Http\Livewire\Storage;

use App\Models\Document;
use App\Models\StorageConnection;
use App\Models\SyncedFile;
use App\Services\Storage\AwsS3Service;
use Livewire\Component;
use Livewire\WithFileUploads;

class S3Browser extends Component
{
    use WithFileUploads;

    public ?int $connectionId = null;

    public ?StorageConnection $connection = null;

    public string $path = '';

    public string $search = '';

    public ?string $nextToken = null;

    /** @var array<int, array<string, mixed>> */
    public array $folders = [];

    /** @var array<int, array<string, mixed>> */
    public array $files = [];

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $uploads = [];

    public string $newFolderName = '';

    // Link-to-document modal
    public bool $showLinkModal = false;

    public ?string $linkFileId = null;

    public ?int $linkDocumentId = null;

    public string $linkMessage = '';

    public string $linkError = '';

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

        $q = StorageConnection::query()->where('provider', 'aws_s3')->where('status', 'connected');
        if ($this->connectionId) {
            $q->where('id', $this->connectionId);
        } else {
            // Default: primary for the user's client
            if ($user?->client_id) {
                $q->where('client_id', $user->client_id)->where('is_primary', true);
            }
        }

        $conn = $q->first();
        if (! $conn) {
            abort(404, 'S3 connection not found.');
        }

        // Staff scoping: staff can only browse assigned clients
        if ($user && $user->hasRole('staff') && ! $user->hasAnyRole(['super_admin', 'admin'])) {
            $allowed = $user->assignedClientIds();
            if (! in_array((int) $conn->client_id, $allowed, true)) {
                abort(403, 'You do not have access to this client storage.');
            }
        }

        // Clients can only browse their own
        if ($user && $user->isClient() && (int) $conn->client_id !== (int) $user->client_id) {
            abort(403, 'You do not have access to this storage connection.');
        }

        $this->connection = $conn;
        $this->connectionId = $conn->id;
    }

    public function updatedPath(): void
    {
        $this->path = trim($this->path, '/');
        $this->refreshListing();
    }

    public function navigateTo(string $path): void
    {
        $this->path = trim($path, '/');
        $this->refreshListing();
    }

    public function refreshListing(): void
    {
        $this->nextToken = null;
        $this->folders = [];
        $this->files = [];

        $svc = app(AwsS3Service::class)->useConnection($this->connection);
        $res = $svc->listFilesPaginated($this->path, null);
        $this->folders = $res['folders'] ?? [];
        $this->files = $res['files'] ?? [];
        $this->nextToken = $res['next_token'] ?? null;

        $this->applySearchFilter();
    }

    public function loadMore(): void
    {
        if (! $this->nextToken) {
            return;
        }
        $svc = app(AwsS3Service::class)->useConnection($this->connection);
        $res = $svc->listFilesPaginated($this->path, $this->nextToken);
        $this->files = array_values(array_merge($this->files, $res['files'] ?? []));
        $this->nextToken = $res['next_token'] ?? null;
        $this->applySearchFilter();
    }

    public function applySearchFilter(): void
    {
        $q = trim($this->search);
        if ($q === '') {
            return;
        }

        $needle = mb_strtolower($q);
        $this->files = array_values(array_filter($this->files, fn ($f) => str_contains(mb_strtolower((string) ($f['name'] ?? '')), $needle)));
        $this->folders = array_values(array_filter($this->folders, fn ($f) => str_contains(mb_strtolower((string) ($f['name'] ?? '')), $needle)));
    }

    public function updatedSearch(): void
    {
        $this->refreshListing();
    }

    public function upload(AwsS3Service $s3): void
    {
        $this->validate([
            'uploads' => ['array'],
            'uploads.*' => ['file', 'max:51200'], // 50MB per file (placeholder)
        ]);

        $s3->useConnection($this->connection);
        foreach ($this->uploads as $file) {
            $s3->uploadFile($file, $this->path);
        }
        $this->uploads = [];

        session()->flash('success', 'Uploaded successfully.');
        $this->refreshListing();
    }

    public function createFolder(AwsS3Service $s3): void
    {
        $name = trim($this->newFolderName);
        if ($name === '') {
            session()->flash('error', 'Folder name is required.');

            return;
        }

        $s3->useConnection($this->connection)->createFolder($name, $this->path);
        $this->newFolderName = '';
        session()->flash('success', 'Folder created.');
        $this->refreshListing();
    }

    public function download(string $fileId, AwsS3Service $s3)
    {
        $url = $s3->useConnection($this->connection)->downloadFile($fileId);

        return redirect()->away($url);
    }

    public function delete(string $fileId, AwsS3Service $s3): void
    {
        $s3->useConnection($this->connection)->deleteFile($fileId);
        session()->flash('success', 'File deleted.');
        $this->refreshListing();
    }

    public function openLinkModal(string $fileId): void
    {
        $this->showLinkModal = true;
        $this->linkFileId = $fileId;
        $this->linkDocumentId = null;
        $this->linkMessage = '';
        $this->linkError = '';
    }

    public function linkToDocument(AwsS3Service $s3): void
    {
        $this->linkMessage = '';
        $this->linkError = '';

        if (! $this->linkFileId) {
            $this->linkError = 'Missing file.';

            return;
        }
        if (! $this->linkDocumentId) {
            $this->linkError = 'Please select a document.';

            return;
        }

        try {
            $meta = $s3->useConnection($this->connection)->head($this->linkFileId);
            $fileName = basename($this->linkFileId);

            SyncedFile::query()->updateOrCreate(
                [
                    'storage_connection_id' => $this->connection->id,
                    'provider_file_id' => $this->linkFileId,
                ],
                [
                    'document_id' => $this->linkDocumentId,
                    'file_name' => $fileName,
                    'file_path' => $this->linkFileId,
                    'file_size' => (int) ($meta['size'] ?? 0),
                    'mime_type' => $meta['mime_type'] ?? null,
                    'last_modified_at' => isset($meta['modified_at']) ? \Carbon\Carbon::parse($meta['modified_at']) : null,
                    'synced_at' => now(),
                    'sync_status' => 'synced',
                ]
            );

            $this->linkMessage = 'Linked successfully.';
            $this->showLinkModal = false;
            session()->flash('success', 'Linked file to document.');
        } catch (\Throwable $e) {
            $this->linkError = $e->getMessage();
        }
    }

    public function getBreadcrumbsProperty(): array
    {
        $parts = $this->path === '' ? [] : explode('/', $this->path);
        $crumbs = [['label' => 'Root', 'path' => '']];
        $acc = '';
        foreach ($parts as $p) {
            $acc = $acc === '' ? $p : ($acc.'/'.$p);
            $crumbs[] = ['label' => $p, 'path' => $acc];
        }

        return $crumbs;
    }

    public function render()
    {
        $docs = Document::query()
            ->where('client_id', $this->connection->client_id)
            ->latest()
            ->limit(200)
            ->get(['id', 'title']);

        return view('livewire.storage.s3-browser', [
            'connection' => $this->connection,
            'breadcrumbs' => $this->breadcrumbs,
            'docs' => $docs,
        ])->layout('layouts.admin', ['title' => 'S3 Browser']);
    }
}
