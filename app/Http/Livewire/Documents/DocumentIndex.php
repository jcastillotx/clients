<?php

namespace App\Http\Livewire\Documents;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Services\Documents\DocumentAccessService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class DocumentIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';

    public string $category = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function deleteDocument(int $documentId): void
    {
        $document = Document::findOrFail($documentId);
        $accessService = app(DocumentAccessService::class);

        // Check if user has permission to delete
        if (! $accessService->canDelete(auth()->user(), $document)) {
            $this->dispatch('notify', message: 'You do not have permission to delete this document.', type: 'error');

            return;
        }

        // Delete the physical file
        if (Storage::disk('documents')->exists($document->file_path)) {
            Storage::disk('documents')->delete($document->file_path);
        }

        // Delete thumbnail if exists
        if ($document->thumbnail_path && Storage::disk('documents')->exists($document->thumbnail_path)) {
            Storage::disk('documents')->delete($document->thumbnail_path);
        }

        ActivityLog::log(
            "Deleted document: {$document->title}",
            $document,
            null,
            'deleted',
            'documents'
        );

        $document->delete();

        $this->dispatch('notify', message: 'Document deleted successfully.', type: 'success');
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();
        $accessService = app(DocumentAccessService::class);

        $query = Document::query()
            ->with(['uploader'])
            ->when($user->isClient(), fn ($q) => $q->where('client_id', $user->client_id))
            ->when($this->search, fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->category !== 'all', fn ($q) => $q->where('category', $this->category))
            ->latest();

        $documents = $query->paginate(15);

        // Add canDelete flag to each document
        $documents->getCollection()->transform(function ($document) use ($user, $accessService) {
            $document->userCanDelete = $accessService->canDelete($user, $document);

            return $document;
        });

        return view('livewire.documents.index', [
            'documents' => $documents,
            'canUpload' => (bool) $user->can('upload_document'),
        ]);
    }
}
