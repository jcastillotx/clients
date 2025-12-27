<?php

namespace App\Http\Livewire\Requests;

use App\Models\Request as ServiceRequest;
use Livewire\Component;
use Livewire\WithPagination;

class RequestIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';

    public string $status = '';

    public string $type = '';

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

    public function delete(int $requestId): void
    {
        $user = auth()->user();

        $request = ServiceRequest::query()->findOrFail($requestId);

        if ($user->isClient()) {
            if ($request->client_id !== $user->client_id) {
                abort(403);
            }

            if (! in_array($request->status, ['draft', 'pending'], true)) {
                abort(403);
            }
        }

        $request->delete();

        session()->flash('success', 'Request deleted.');
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        $query = ServiceRequest::query()
            ->when($user->isClient(), fn ($q) => $q->where('client_id', $user->client_id))
            ->when($this->search, fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->latest();

        return view('livewire.requests.index', [
            'requests' => $query->paginate(15),
            'statuses' => array_keys(config('client-portal.request_statuses', [])),
            'types' => array_keys(config('client-portal.request_types', [])),
        ]);
    }
}
