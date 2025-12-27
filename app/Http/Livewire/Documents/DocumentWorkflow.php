<?php

namespace App\Http\Livewire\Documents;

use App\Models\Contract;
use App\Models\Document;
use App\Models\DocumentComment;
use App\Models\DocumentLink;
use App\Models\DocumentVersion;
use App\Models\Invoice;
use App\Models\Request;
use App\Models\StorageFile;
use App\Services\Documents\DocumentAccessService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithFileUploads;

class DocumentWorkflow extends Component
{
    use WithFileUploads;

    public Document $document;

    public string $commentBody = '';

    public bool $commentInternal = false;

    // versioning
    public $newVersionUpload;

    // linking
    public string $linkTargetType = 'request';

    public ?int $linkTargetId = null;

    public string $linkPurpose = 'supporting';

    public ?int $linkStorageFileId = null;

    // compare
    public ?int $compareA = null;

    public ?int $compareB = null;

    public ?string $compareTextA = null;

    public ?string $compareTextB = null;

    public array $diffA = [];

    public array $diffB = [];

    public function mount(Document $document): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $access = app(DocumentAccessService::class);
        abort_unless($access->canView($user, $document), 403);

        $this->document = $document->load(['client', 'request', 'uploader', 'reviewer']);
    }

    public function submitForReview(): void
    {
        $this->authorizeAdmin();
        $this->document->update([
            'status' => 'pending_review',
            'submitted_at' => now(),
            'approved_at' => null,
            'rejected_at' => null,
            'reviewed_by' => null,
        ]);
        DocumentComment::create([
            'document_id' => $this->document->id,
            'user_id' => Auth::id(),
            'body' => 'Submitted for client review.',
            'is_internal' => true,
        ]);
        session()->flash('success', 'Document submitted for review.');
    }

    public function approve(): void
    {
        $this->authorizeClientAction();
        $this->document->update([
            'status' => 'approved',
            'approved_at' => now(),
            'rejected_at' => null,
            'reviewed_by' => Auth::id(),
        ]);
        DocumentComment::create([
            'document_id' => $this->document->id,
            'user_id' => Auth::id(),
            'body' => 'Approved.',
            'is_internal' => false,
        ]);
        session()->flash('success', 'Document approved.');
    }

    public function reject(): void
    {
        $this->authorizeClientAction();

        Validator::make(['comment' => $this->commentBody], [
            'comment' => ['required', 'string', 'min:3'],
        ])->validate();

        $this->document->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'approved_at' => null,
            'reviewed_by' => Auth::id(),
        ]);
        DocumentComment::create([
            'document_id' => $this->document->id,
            'user_id' => Auth::id(),
            'body' => 'Rejected: '.$this->commentBody,
            'is_internal' => false,
        ]);
        $this->commentBody = '';
        session()->flash('success', 'Feedback submitted.');
    }

    public function addComment(): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        Validator::make([
            'body' => $this->commentBody,
        ], [
            'body' => ['required', 'string', 'min:1'],
        ])->validate();

        if ($user->isClient()) {
            $this->commentInternal = false;
        }

        DocumentComment::create([
            'document_id' => $this->document->id,
            'user_id' => $user->id,
            'body' => $this->commentBody,
            'is_internal' => $this->commentInternal,
        ]);

        $this->commentBody = '';
        $this->commentInternal = false;
    }

    public function uploadNewVersion(): void
    {
        $user = Auth::user();
        abort_unless($user, 403);
        $access = app(DocumentAccessService::class);
        abort_unless($access->canUploadNewVersion($user, $this->document), 403);

        Validator::make(['file' => $this->newVersionUpload], [
            'file' => ['required', 'file', 'max:51200'],
        ])->validate();

        // Persist old current file as a version if version 1 doesn't exist yet
        $current = $this->document;
        $existingV1 = DocumentVersion::query()->where('document_id', $current->id)->where('version', 1)->exists();
        if (! $existingV1) {
            DocumentVersion::create([
                'document_id' => $current->id,
                'version' => 1,
                'disk' => 'documents',
                'file_path' => $current->file_path,
                'original_filename' => $current->original_filename,
                'mime_type' => $current->mime_type,
                'file_size' => $current->file_size,
                'uploaded_by' => $current->uploaded_by,
            ]);
        }

        $nextVersion = ((int) $current->current_version) + 1;
        $storedPath = $this->newVersionUpload->store('versions', 'documents');
        $original = $this->newVersionUpload->getClientOriginalName();
        $mime = $this->newVersionUpload->getClientMimeType();
        $size = $this->newVersionUpload->getSize();

        DocumentVersion::create([
            'document_id' => $current->id,
            'version' => $nextVersion,
            'disk' => 'documents',
            'file_path' => $storedPath,
            'original_filename' => $original,
            'mime_type' => $mime,
            'file_size' => $size,
            'uploaded_by' => $user->id,
        ]);

        $current->update([
            'file_path' => $storedPath,
            'original_filename' => $original,
            'mime_type' => $mime,
            'file_size' => $size,
            'current_version' => $nextVersion,
            'status' => 'draft',
            'submitted_at' => null,
            'approved_at' => null,
            'rejected_at' => null,
            'reviewed_by' => null,
        ]);

        $this->newVersionUpload = null;

        DocumentComment::create([
            'document_id' => $current->id,
            'user_id' => $user->id,
            'body' => "Uploaded new version v{$nextVersion}.",
            'is_internal' => $user->isClient() ? false : true,
        ]);

        session()->flash('success', 'New version uploaded.');
    }

    public function linkToEntity(): void
    {
        $this->authorizeAdmin();
        Validator::make([
            'type' => $this->linkTargetType,
            'id' => $this->linkTargetId,
        ], [
            'type' => ['required', 'in:request,invoice,contract'],
            'id' => ['required', 'integer', 'min:1'],
        ])->validate();

        $target = match ($this->linkTargetType) {
            'invoice' => Invoice::query()->findOrFail($this->linkTargetId),
            'contract' => Contract::query()->findOrFail($this->linkTargetId),
            default => Request::query()->findOrFail($this->linkTargetId),
        };

        // enforce same client ownership when applicable
        if (property_exists($target, 'client_id') && (int) $target->client_id !== (int) $this->document->client_id) {
            abort(403);
        }

        DocumentLink::firstOrCreate([
            'source_type' => Document::class,
            'source_id' => $this->document->id,
            'linkable_type' => get_class($target),
            'linkable_id' => $target->id,
            'purpose' => $this->linkPurpose,
        ]);

        session()->flash('success', 'Linked.');
    }

    public function linkStorageFileToEntity(): void
    {
        $this->authorizeAdmin();
        Validator::make([
            'storage_file_id' => $this->linkStorageFileId,
            'type' => $this->linkTargetType,
            'id' => $this->linkTargetId,
        ], [
            'storage_file_id' => ['required', 'integer', 'exists:storage_files,id'],
            'type' => ['required', 'in:request,invoice,contract'],
            'id' => ['required', 'integer', 'min:1'],
        ])->validate();

        $file = StorageFile::query()->with('connection')->findOrFail($this->linkStorageFileId);
        abort_unless((int) $file->connection->client_id === (int) $this->document->client_id, 403);

        $target = match ($this->linkTargetType) {
            'invoice' => Invoice::query()->findOrFail($this->linkTargetId),
            'contract' => Contract::query()->findOrFail($this->linkTargetId),
            default => Request::query()->findOrFail($this->linkTargetId),
        };
        if (property_exists($target, 'client_id') && (int) $target->client_id !== (int) $this->document->client_id) {
            abort(403);
        }

        DocumentLink::firstOrCreate([
            'source_type' => StorageFile::class,
            'source_id' => $file->id,
            'linkable_type' => get_class($target),
            'linkable_id' => $target->id,
            'purpose' => $this->linkPurpose,
        ]);

        session()->flash('success', 'Cloud file linked.');
    }

    public function compareVersions(): void
    {
        $user = Auth::user();
        abort_unless($user, 403);
        $access = app(DocumentAccessService::class);
        abort_unless($access->canView($user, $this->document), 403);

        $a = $this->compareA ? DocumentVersion::query()->where('document_id', $this->document->id)->find($this->compareA) : null;
        $b = $this->compareB ? DocumentVersion::query()->where('document_id', $this->document->id)->find($this->compareB) : null;

        $this->compareTextA = $this->readTextVersion($a);
        $this->compareTextB = $this->readTextVersion($b);

        $this->diffA = [];
        $this->diffB = [];
        if ($this->compareTextA && $this->compareTextB
            && ! str_starts_with($this->compareTextA, '(')
            && ! str_starts_with($this->compareTextB, '(')
        ) {
            [$this->diffA, $this->diffB] = $this->buildSideBySideDiff($this->compareTextA, $this->compareTextB);
        }
    }

    protected function buildSideBySideDiff(string $a, string $b): array
    {
        $aLines = preg_split("/\r\n|\r|\n/", $a) ?: [];
        $bLines = preg_split("/\r\n|\r|\n/", $b) ?: [];

        $max = max(count($aLines), count($bLines));
        $outA = [];
        $outB = [];

        for ($i = 0; $i < $max; $i++) {
            $la = $aLines[$i] ?? '';
            $lb = $bLines[$i] ?? '';
            $changed = $la !== $lb;
            $outA[] = ['n' => $i + 1, 'text' => $la, 'changed' => $changed];
            $outB[] = ['n' => $i + 1, 'text' => $lb, 'changed' => $changed];
        }

        return [$outA, $outB];
    }

    protected function readTextVersion(?DocumentVersion $v): ?string
    {
        if (! $v) {
            return null;
        }
        $mime = strtolower((string) $v->mime_type);
        $isText = str_starts_with($mime, 'text/')
            || in_array(strtolower(pathinfo($v->original_filename, PATHINFO_EXTENSION)), ['txt', 'md', 'csv', 'json', 'xml', 'html'], true);
        if (! $isText) {
            return '(Comparison not available for this file type.)';
        }
        try {
            $bytes = Storage::disk($v->disk)->get($v->file_path);
            if (strlen($bytes) > 200_000) {
                return '(File too large to compare.)';
            }

            return $bytes;
        } catch (\Throwable) {
            return '(Unable to read file contents.)';
        }
    }

    protected function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->can('manage documents') || Auth::user()?->can('access admin panel'), 403);
    }

    protected function authorizeClientAction(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);
        abort_unless((int) $user->client_id === (int) $this->document->client_id, 403);
        abort_unless($this->document->status === 'pending_review', 422);
    }

    public function render()
    {
        $user = Auth::user();
        $comments = $this->document->comments()
            ->with('user')
            ->when($user?->isClient(), fn ($q) => $q->where('is_internal', false))
            ->get();

        $versions = $this->document->versions()->get();

        $links = DocumentLink::query()
            ->where(function ($q) {
                $q->where('source_type', Document::class)->where('source_id', $this->document->id);
            })
            ->with('linkable')
            ->latest('id')
            ->get();

        $availableStorageFiles = StorageFile::query()
            ->whereHas('connection', fn ($q) => $q->where('client_id', $this->document->client_id)->where('status', 'active'))
            ->with('connection')
            ->latest('id')
            ->limit(50)
            ->get();

        return view('livewire.documents.workflow', compact('comments', 'versions', 'links', 'availableStorageFiles'));
    }
}
