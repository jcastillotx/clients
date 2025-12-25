<?php

namespace App\Http\Livewire\Storage;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Document;
use App\Models\Request as ServiceRequest;
use App\Models\StorageConnection;
use App\Models\SyncedFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class UnifiedFileBrowser extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public ?int $client_id = null;
    public string $provider = 'all'; // all|aws_s3|dropbox|google_drive
    public string $type = 'all'; // all|pdf|image|doc|sheet|archive|other
    public string $date_from = '';
    public string $date_to = '';
    public string $search = '';
    public bool $conflicts_only = false;

    public bool $linkModalOpen = false;
    public ?int $selectedSyncedFileId = null;
    public string $linkTarget = 'document'; // document|request|contract
    public ?int $linkDocumentId = null;
    public ?int $linkRequestId = null;
    public ?int $linkContractId = null;
    public string $tagsInput = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingProvider(): void { $this->resetPage(); }
    public function updatingType(): void { $this->resetPage(); }
    public function updatingDateFrom(): void { $this->resetPage(); }
    public function updatingDateTo(): void { $this->resetPage(); }
    public function updatingClientId(): void { $this->resetPage(); }
    public function updatingConflictsOnly(): void { $this->resetPage(); }

    protected function allowedClientIds(): array
    {
        $user = auth()->user();
        if (!$user) return [];

        if ($user->isClient()) {
            return [(int) $user->client_id];
        }

        if ($user->hasRole('staff') && !$user->hasAnyRole(['super_admin', 'admin'])) {
            return $user->assignedClientIds();
        }

        // Admin/super admin: all (but can be filtered by client_id).
        return [];
    }

    protected function baseQuery(): Builder
    {
        $allowed = $this->allowedClientIds();

        $q = SyncedFile::query()
            ->with(['storageConnection.client'])
            ->whereHas('storageConnection', function ($qq) use ($allowed) {
                /** @var Builder $qq */
                if (!empty($allowed)) {
                    $qq->whereIn('client_id', $allowed);
                }
                if ($this->client_id) {
                    $qq->where('client_id', $this->client_id);
                }
                if ($this->provider !== 'all') {
                    $qq->where('provider', $this->provider);
                }
            });

        if ($this->search !== '') {
            $s = '%' . $this->search . '%';
            $q->where('file_name', 'like', $s);
        }

        if ($this->date_from !== '') {
            $q->whereDate('last_modified_at', '>=', $this->date_from);
        }
        if ($this->date_to !== '') {
            $q->whereDate('last_modified_at', '<=', $this->date_to);
        }

        if ($this->type !== 'all') {
            $q->where(function ($qq) {
                /** @var Builder $qq */
                $t = $this->type;
                if ($t === 'pdf') {
                    $qq->where('mime_type', 'application/pdf');
                } elseif ($t === 'image') {
                    $qq->where('mime_type', 'like', 'image/%');
                } elseif ($t === 'doc') {
                    $qq->whereIn('mime_type', [
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.google-apps.document',
                    ]);
                } elseif ($t === 'sheet') {
                    $qq->whereIn('mime_type', [
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.google-apps.spreadsheet',
                    ]);
                } elseif ($t === 'archive') {
                    $qq->whereIn('mime_type', ['application/zip']);
                } else {
                    $qq->where(function ($x) {
                        $x->whereNull('mime_type')
                            ->orWhere('mime_type', '=', '')
                            ->orWhereNotIn('mime_type', [
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/zip',
                            ])
                            ->where('mime_type', 'not like', 'image/%');
                    });
                }
            });
        }

        if ($this->conflicts_only && $this->client_id) {
            $conflictNames = SyncedFile::query()
                ->select('file_name')
                ->join('storage_connections', 'storage_connections.id', '=', 'synced_files.storage_connection_id')
                ->where('storage_connections.client_id', $this->client_id)
                ->groupBy('file_name')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('file_name')
                ->all();

            if (!empty($conflictNames)) {
                $q->whereIn('file_name', $conflictNames);
            } else {
                $q->whereRaw('1=0');
            }
        }

        return $q->orderByDesc('last_modified_at')->orderByDesc('id');
    }

    public function openLinkModal(int $syncedFileId): void
    {
        $sf = SyncedFile::query()->with('storageConnection')->findOrFail($syncedFileId);
        $this->authorizeSyncedFile($sf);

        $this->selectedSyncedFileId = $sf->id;
        $this->linkTarget = 'document';
        $this->linkDocumentId = $sf->document_id ? (int) $sf->document_id : null;
        $this->linkRequestId = $sf->request_id ? (int) $sf->request_id : null;
        $this->linkContractId = $sf->contract_id ? (int) $sf->contract_id : null;
        $this->tagsInput = collect((array) ($sf->tags ?? []))->implode(', ');
        $this->linkModalOpen = true;
    }

    public function closeLinkModal(): void
    {
        $this->linkModalOpen = false;
        $this->selectedSyncedFileId = null;
        $this->linkDocumentId = null;
        $this->linkRequestId = null;
        $this->linkContractId = null;
        $this->tagsInput = '';
    }

    public function saveLinksAndTags(): void
    {
        if (!$this->selectedSyncedFileId) {
            return;
        }

        $sf = SyncedFile::query()->with('storageConnection')->findOrFail($this->selectedSyncedFileId);
        $this->authorizeSyncedFile($sf);

        $tags = collect(explode(',', $this->tagsInput))
            ->map(fn ($t) => trim($t))
            ->filter(fn ($t) => $t !== '')
            ->unique()
            ->values()
            ->all();

        $docId = null;
        $reqId = null;
        $ctrId = null;
        if ($this->linkTarget === 'document') {
            $docId = $this->linkDocumentId;
        } elseif ($this->linkTarget === 'request') {
            $reqId = $this->linkRequestId;
        } elseif ($this->linkTarget === 'contract') {
            $ctrId = $this->linkContractId;
        }

        // Validate ownership for selected targets.
        $clientId = (int) $sf->storageConnection->client_id;
        if ($docId) {
            $doc = Document::query()->findOrFail($docId);
            if ((int) $doc->client_id !== $clientId) abort(403);
        }
        if ($reqId) {
            $req = ServiceRequest::query()->findOrFail($reqId);
            if ((int) $req->client_id !== $clientId) abort(403);
        }
        if ($ctrId) {
            $ctr = Contract::query()->findOrFail($ctrId);
            if ((int) $ctr->client_id !== $clientId) abort(403);
        }

        $sf->update([
            'document_id' => $docId,
            'request_id' => $reqId,
            'contract_id' => $ctrId,
            'tags' => $tags,
        ]);

        session()->flash('success', 'Links/tags saved.');
        $this->closeLinkModal();
    }

    protected function authorizeSyncedFile(SyncedFile $sf): void
    {
        $user = auth()->user();
        if (!$user) abort(403);

        $clientId = (int) $sf->storageConnection->client_id;
        if ($user->isClient()) {
            abort_if((int) $user->client_id !== $clientId, 403);
            return;
        }

        if ($user->hasRole('staff') && !$user->hasAnyRole(['super_admin', 'admin'])) {
            abort_if(!in_array($clientId, $user->assignedClientIds(), true), 403);
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

        $files = $this->baseQuery()->paginate(25);

        $clientForOptions = $this->client_id ?: ($user?->client_id);
        $documents = $clientForOptions
            ? Document::query()->where('client_id', $clientForOptions)->latest()->limit(100)->get(['id', 'title'])
            : collect();
        $requests = $clientForOptions
            ? ServiceRequest::query()->where('client_id', $clientForOptions)->latest()->limit(100)->get(['id', 'title', 'status'])
            : collect();
        $contracts = $clientForOptions
            ? Contract::query()->where('client_id', $clientForOptions)->latest()->limit(100)->get(['id', 'title', 'status'])
            : collect();

        return view('livewire.storage.unified-browser', [
            'isAdmin' => $isAdmin,
            'clients' => $clients,
            'files' => $files,
            'documents' => $documents,
            'requests' => $requests,
            'contracts' => $contracts,
        ])->layout('layouts.admin', ['title' => 'Unified Files']);
    }
}

