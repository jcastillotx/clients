<?php

namespace App\Http\Livewire\Storage;

use App\Models\Contract;
use App\Models\Document;
use App\Models\Request;
use App\Models\StorageConnection;
use App\Models\StorageFile;
use App\Models\StorageFileLink;
use App\Models\StorageTag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithPagination;

class UnifiedFileBrowser extends Component
{
    use WithPagination;

    public ?int $clientId = null;

    public string $search = '';

    public string $provider = '';

    public string $fileType = '';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    // tagging
    public string $newTagName = '';

    public ?int $selectedFileId = null;

    // linking
    public string $linkType = 'request';

    public ?int $linkId = null;

    public string $linkPurpose = 'reference';

    protected array $queryString = [
        'search' => ['except' => ''],
        'provider' => ['except' => ''],
        'fileType' => ['except' => ''],
        'dateFrom' => ['except' => null],
        'dateTo' => ['except' => null],
    ];

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
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingProvider(): void
    {
        $this->resetPage();
    }

    public function updatingFileType(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function selectFile(int $fileId): void
    {
        $file = $this->fileOrFail($fileId);
        $this->selectedFileId = $file->id;
    }

    public function addTagToSelected(): void
    {
        $file = $this->fileOrFail((int) $this->selectedFileId);

        Validator::make(['name' => $this->newTagName], [
            'name' => ['required', 'string', 'max:50'],
        ])->validate();

        $tag = StorageTag::firstOrCreate(
            ['client_id' => $this->clientId, 'name' => trim($this->newTagName)],
            ['color' => null]
        );

        $file->tags()->syncWithoutDetaching([$tag->id]);
        $this->newTagName = '';
        session()->flash('success', 'Tag added.');
    }

    public function linkSelected(): void
    {
        $file = $this->fileOrFail((int) $this->selectedFileId);

        Validator::make([
            'linkType' => $this->linkType,
            'linkId' => $this->linkId,
            'purpose' => $this->linkPurpose,
        ], [
            'linkType' => ['required', 'in:document,request,contract'],
            'linkId' => ['required', 'integer', 'min:1'],
            'purpose' => ['nullable', 'string', 'max:60'],
        ])->validate();

        $model = match ($this->linkType) {
            'document' => Document::query()->findOrFail($this->linkId),
            'contract' => Contract::query()->findOrFail($this->linkId),
            default => Request::query()->findOrFail($this->linkId),
        };

        // Ensure same client ownership for safety
        if (property_exists($model, 'client_id') && (int) $model->client_id !== (int) $this->clientId) {
            abort(403);
        }

        StorageFileLink::firstOrCreate([
            'storage_file_id' => $file->id,
            'linkable_type' => get_class($model),
            'linkable_id' => $model->id,
            'purpose' => $this->linkPurpose,
        ]);

        session()->flash('success', 'File linked.');
    }

    protected function fileOrFail(int $fileId): StorageFile
    {
        $file = StorageFile::query()
            ->with(['connection', 'tags'])
            ->findOrFail($fileId);

        abort_unless((int) $file->connection->client_id === (int) $this->clientId, 403);

        return $file;
    }

    public function render()
    {
        $connections = StorageConnection::query()
            ->where('client_id', $this->clientId)
            ->where('status', 'active')
            ->orderByDesc('is_primary')
            ->get();

        $query = StorageFile::query()
            ->with(['connection', 'tags'])
            ->whereIn('storage_connection_id', $connections->pluck('id'))
            ->when($this->provider, function ($q) use ($connections) {
                $ids = $connections->where('provider', $this->provider)->pluck('id');
                $q->whereIn('storage_connection_id', $ids);
            })
            ->when($this->search, function ($q) {
                $q->where(function ($qq) {
                    $qq->where('filename', 'like', '%'.$this->search.'%')
                        ->orWhere('path', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->fileType, function ($q) {
                $q->where(function ($qq) {
                    $qq->where('extension', $this->fileType)
                        ->orWhere('mime_type', 'like', $this->fileType.'%');
                });
            })
            ->when($this->dateFrom, fn ($q) => $q->whereDate('modified_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('modified_at', '<=', $this->dateTo))
            ->orderByDesc('modified_at')
            ->orderByDesc('id');

        $tags = StorageTag::query()->where('client_id', $this->clientId)->orderBy('name')->get();

        return view('livewire.storage.unified-browser', [
            'connections' => $connections,
            'files' => $query->paginate(20),
            'tags' => $tags,
        ]);
    }
}
