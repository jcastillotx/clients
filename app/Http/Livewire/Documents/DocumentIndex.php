<?php

namespace App\Http\Livewire\Documents;

use App\Models\Document;
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

    public function render()
    {
        $user = auth()->user();

        $query = Document::query()
            ->with(['uploader'])
            ->when($user->isClient(), fn ($q) => $q->where('client_id', $user->client_id))
            ->when($this->search, fn ($q) => $q->where('title', 'like', '%' . $this->search . '%'))
            ->when($this->category !== 'all', fn ($q) => $q->where('category', $this->category))
            ->latest();

        return view('livewire.documents.index', [
            'documents' => $query->paginate(15),
            'canUpload' => (bool) $user->can('upload_document'),
        ]);
    }
}

