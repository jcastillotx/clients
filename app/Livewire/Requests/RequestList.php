<?php

namespace App\Livewire\Requests;

use App\Models\Request;
use Livewire\Component;
use Livewire\WithPagination;

class RequestList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $type = '';
    public string $priority = '';
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'type' => ['except' => ''],
        'priority' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function updatingPriority(): void
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
        $this->reset(['search', 'status', 'type', 'priority']);
    }

    public function render()
    {
        $user = auth()->user();

        $query = Request::query()
            ->with(['client', 'assignee'])
            ->when($user->isClient(), function ($q) use ($user) {
                $q->where('client_id', $user->client_id);
            })
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('title', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status, function ($q) {
                $q->where('status', $this->status);
            })
            ->when($this->type, function ($q) {
                $q->where('type', $this->type);
            })
            ->when($this->priority, function ($q) {
                $q->where('priority', $this->priority);
            })
            ->orderBy($this->sortField, $this->sortDirection);

        return view('livewire.requests.request-list', [
            'requests' => $query->paginate(10),
            'statuses' => config('client-portal.request_statuses'),
            'types' => config('client-portal.request_types'),
            'priorities' => config('client-portal.request_priorities'),
        ]);
    }
}
