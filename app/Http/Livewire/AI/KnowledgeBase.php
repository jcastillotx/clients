<?php

namespace App\Http\Livewire\AI;

use App\Models\Document;
use App\Models\KnowledgeBaseDocument;
use App\Services\AI\DocumentEmbeddingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class KnowledgeBase extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    /** @var array<int, bool> */
    public array $included = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggleInclude(int $documentId, DocumentEmbeddingService $embeddings): void
    {
        $this->authorizeAdmin();

        $exists = KnowledgeBaseDocument::query()->where('document_id', $documentId)->exists();
        if ($exists) {
            KnowledgeBaseDocument::query()->where('document_id', $documentId)->delete();
            session()->flash('success', 'Removed from knowledge base.');

            return;
        }

        KnowledgeBaseDocument::create(['document_id' => $documentId]);

        // Best-effort: generate embedding.
        $doc = Document::query()->find($documentId);
        if ($doc) {
            $embeddings->upsertDocumentEmbedding($doc, ['provider' => 'openai', 'model' => 'text-embedding-3-small']);
        }

        session()->flash('success', 'Added to knowledge base (embedding queued/best-effort).');
    }

    protected function authorizeAdmin(): void
    {
        $u = Auth::user();
        if (! $u || ! $u->can('access admin panel')) {
            abort(403);
        }
    }

    public function render()
    {
        $this->authorizeAdmin();

        $kbIds = KnowledgeBaseDocument::query()->pluck('document_id')->all();

        $docs = Document::query()
            ->orderByDesc('id')
            ->when(trim($this->search) !== '', function ($q) {
                $s = '%'.trim($this->search).'%';
                $q->where('title', 'like', $s)->orWhere('original_filename', 'like', $s);
            })
            ->paginate(20);

        return view('livewire.ai.knowledge-base', [
            'docs' => $docs,
            'kbIds' => $kbIds,
        ])->layout('layouts.admin', ['title' => 'AI Knowledge Base']);
    }
}
