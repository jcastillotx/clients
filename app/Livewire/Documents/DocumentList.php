<?php

namespace App\Livewire\Documents;

use App\Models\Document;
use Livewire\Component;
use Livewire\WithPagination;

class DocumentList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $category = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'category' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'category']);
    }

    public function render()
    {
        $user = auth()->user();

        $query = Document::query()
            ->with(['client', 'uploader'])
            ->when($user->isClient(), function ($q) use ($user) {
                $q->where('client_id', $user->client_id);
            })
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('original_filename', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->category, function ($q) {
                $q->where('category', $this->category);
            })
            ->orderBy($this->sortField, $this->sortDirection);

        return view('livewire.documents.document-list', [
            'documents' => $query->paginate(12),
            'categories' => config('client-portal.document_categories'),
        ]);
    }
}
