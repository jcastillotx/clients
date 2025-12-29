<?php

namespace App\Http\Livewire\Documents;

use App\Models\Document;
use App\Models\DocumentLink;
use App\Models\DocumentShare;
use App\Models\Request;
use App\Models\StorageFile;
use App\Models\StorageTag;
use App\Services\Documents\DocumentAccessService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Component;

class SmartDocumentBrowser extends Component
{
    public string $search = '';

    public string $provider = ''; // local|s3|dropbox|drive

    public string $fileType = '';

    public string $linkedEntity = ''; // request|invoice|contract

    public ?int $tagId = null;

    public array $selected = []; // ["document:1", "storage_file:2"]

    // bulk link
    public string $bulkLinkType = 'request';

    public ?int $bulkLinkId = null;

    public string $bulkPurpose = 'supporting';

    // bulk tag
    public ?int $bulkTagId = null;

    protected array $queryString = [
        'search' => ['except' => ''],
        'provider' => ['except' => ''],
        'fileType' => ['except' => ''],
        'linkedEntity' => ['except' => ''],
        'tagId' => ['except' => null],
    ];

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        // Clients must have a client_id for scoping; staff/admin can browse globally.
        if ($user->isClient() && ! $user->client_id) {
            abort(403, 'No client associated with this account.');
        }

        // Prevent “other” authenticated users from seeing everything by default.
        if (! $user->isClient() && ! $user->can('access admin panel')) {
            abort(403);
        }
    }

    public function toggleSelect(string $key): void
    {
        if (in_array($key, $this->selected, true)) {
            $this->selected = array_values(array_filter($this->selected, fn ($x) => $x !== $key));
        } else {
            $this->selected[] = $key;
        }
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function bulkLink(): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        Validator::make([
            'type' => $this->bulkLinkType,
            'id' => $this->bulkLinkId,
            'selected' => $this->selected,
        ], [
            'type' => ['required', 'in:request,invoice,contract'],
            'id' => ['required', 'integer', 'min:1'],
            'selected' => ['required', 'array', 'min:1'],
        ])->validate();

        $target = match ($this->bulkLinkType) {
            'request' => \App\Models\Request::query()->findOrFail($this->bulkLinkId),
            'invoice' => \App\Models\Invoice::query()->findOrFail($this->bulkLinkId),
            default => \App\Models\Contract::query()->findOrFail($this->bulkLinkId),
        };

        // client-scope: allow staff/admin; clients can only link within their client
        if ($user->isClient() && (int) $target->client_id !== (int) $user->client_id) {
            abort(403);
        }

        foreach ($this->selected as $sel) {
            [$type, $id] = explode(':', $sel, 2);
            $id = (int) $id;
            if ($type === 'document') {
                $doc = Document::query()->findOrFail($id);
                if ($user->isClient() && (int) $doc->client_id !== (int) $user->client_id) {
                    continue;
                }
                DocumentLink::firstOrCreate([
                    'source_type' => Document::class,
                    'source_id' => $doc->id,
                    'linkable_type' => get_class($target),
                    'linkable_id' => $target->id,
                    'purpose' => $this->bulkPurpose,
                ]);
            } elseif ($type === 'storage_file') {
                $sf = StorageFile::query()->with('connection')->findOrFail($id);
                if ($user->isClient() && (int) $sf->connection->client_id !== (int) $user->client_id) {
                    continue;
                }
                DocumentLink::firstOrCreate([
                    'source_type' => StorageFile::class,
                    'source_id' => $sf->id,
                    'linkable_type' => get_class($target),
                    'linkable_id' => $target->id,
                    'purpose' => $this->bulkPurpose,
                ]);
            }
        }

        session()->flash('success', 'Linked selected items.');
        $this->clearSelection();
    }

    public function bulkTag(): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        Validator::make([
            'tag' => $this->bulkTagId,
            'selected' => $this->selected,
        ], [
            'tag' => ['required', 'integer', 'exists:storage_tags,id'],
            'selected' => ['required', 'array', 'min:1'],
        ])->validate();

        $tag = StorageTag::query()->findOrFail($this->bulkTagId);
        if ($user->isClient() && (int) $tag->client_id !== (int) $user->client_id) {
            abort(403);
        }

        foreach ($this->selected as $sel) {
            [$type, $id] = explode(':', $sel, 2);
            $id = (int) $id;
            if ($type === 'document') {
                $doc = Document::query()->findOrFail($id);
                if ($user->isClient() && (int) $doc->client_id !== (int) $user->client_id) {
                    continue;
                }
                $doc->tags()->syncWithoutDetaching([$tag->id]);
            } elseif ($type === 'storage_file') {
                $sf = StorageFile::query()->with('connection')->findOrFail($id);
                if ($user->isClient() && (int) $sf->connection->client_id !== (int) $user->client_id) {
                    continue;
                }
                $sf->tags()->syncWithoutDetaching([$tag->id]);
            }
        }

        session()->flash('success', 'Tagged selected items.');
        $this->clearSelection();
    }

    public function createShare(string $sel): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        [$type, $id] = explode(':', $sel, 2);
        $id = (int) $id;

        $source = null;
        if ($type === 'document') {
            $source = Document::query()->findOrFail($id);
            $access = app(DocumentAccessService::class);
            abort_unless($access->canShare($user, $source), 403);
        } elseif ($type === 'storage_file') {
            $source = StorageFile::query()->with('connection')->findOrFail($id);
            if ($user->isClient() && (int) $source->connection->client_id !== (int) $user->client_id) {
                abort(403);
            }
        } else {
            abort(422);
        }

        $share = DocumentShare::create([
            'source_type' => get_class($source),
            'source_id' => $source->id,
            'token' => Str::random(40),
            'expires_at' => now()->addDays(7),
            'max_downloads' => 25,
            'permissions' => ['download' => true],
            'created_by' => $user->id,
        ]);

        session()->flash('success', 'Share link created: '.route('documents.share.download', $share->token));
    }

    public function render()
    {
        $user = Auth::user();
        $clientId = $user?->client_id;

        // Build a unified union query for listing (lightweight columns only)
        $docQuery = DB::table('documents')
            ->selectRaw("documents.id as id, 'document' as item_type, documents.title as title, documents.original_filename as filename, 'local' as provider, documents.mime_type as mime_type, documents.file_size as size_bytes, documents.updated_at as modified_at")
            ->whereNull('documents.deleted_at')
            ->when($clientId, fn ($q) => $q->where('documents.client_id', $clientId));

        $sfQuery = DB::table('storage_files')
            ->join('storage_connections', 'storage_connections.id', '=', 'storage_files.storage_connection_id')
            ->selectRaw("storage_files.id as id, 'storage_file' as item_type, storage_files.filename as title, storage_files.filename as filename, storage_connections.provider as provider, storage_files.mime_type as mime_type, storage_files.size_bytes as size_bytes, COALESCE(storage_files.modified_at, storage_files.updated_at) as modified_at")
            ->when($clientId, fn ($q) => $q->where('storage_connections.client_id', $clientId));

        $union = $docQuery->unionAll($sfQuery);

        $rows = DB::query()
            ->fromSub($union, 'u')
            ->when($this->provider, fn ($q) => $q->where('provider', $this->provider))
            ->when($this->search, function ($q) {
                $q->where(function ($qq) {
                    $qq->where('filename', 'like', '%'.$this->search.'%')
                        ->orWhere('title', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->fileType, function ($q) {
                $q->where(function ($qq) {
                    $qq->where('mime_type', 'like', $this->fileType.'%')
                        ->orWhere('filename', 'like', '%.'.$this->fileType);
                });
            })
            ->orderByDesc('modified_at')
            ->orderByDesc('id')
            ->get();

        // Hydrate minimal view models
        $items = collect($rows)->map(function ($r) {
            return (object) [
                'key' => $r->item_type.':'.$r->id,
                'item_type' => $r->item_type,
                'id' => (int) $r->id,
                'title' => $r->title,
                'filename' => $r->filename,
                'provider' => $r->provider,
                'mime_type' => $r->mime_type,
                'size_bytes' => (int) $r->size_bytes,
                'modified_at' => $r->modified_at,
            ];
        });

        // Filter by linked entity / tag (post-filter to keep union simple)
        if ($this->linkedEntity) {
            $wantedType = match ($this->linkedEntity) {
                'invoice' => \App\Models\Invoice::class,
                'contract' => \App\Models\Contract::class,
                default => \App\Models\Request::class,
            };
            $linked = DocumentLink::query()
                ->where('linkable_type', $wantedType)
                ->select(['source_type', 'source_id'])
                ->get()
                ->map(fn ($l) => ($l->source_type === Document::class ? 'document:' : 'storage_file:').$l->source_id)
                ->flip();
            $items = $items->filter(fn ($i) => isset($linked[$i->key]))->values();
        }

        if ($this->tagId) {
            $tagId = (int) $this->tagId;
            $docIds = DB::table('document_tag')->where('storage_tag_id', $tagId)->pluck('document_id')->all();
            $sfIds = DB::table('storage_file_tag')->where('storage_tag_id', $tagId)->pluck('storage_file_id')->all();
            $allowed = collect(array_merge(
                array_map(fn ($id) => 'document:'.$id, $docIds),
                array_map(fn ($id) => 'storage_file:'.$id, $sfIds),
            ))->flip();
            $items = $items->filter(fn ($i) => isset($allowed[$i->key]))->values();
        }

        // manual pagination
        $page = (int) request()->query('page', 1);
        $perPage = 20;
        $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();
        $paginator = new LengthAwarePaginator($slice, $items->count(), $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);

        $tags = StorageTag::query()
            ->when($clientId, fn ($q) => $q->where('client_id', $clientId))
            ->orderBy('name')
            ->get();

        return view('livewire.documents.smart-browser', [
            'items' => $paginator,
            'tags' => $tags,
        ]);
    }
}
