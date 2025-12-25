<?php

namespace App\Http\Livewire\Admin;

use App\Models\ActivityLog;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityLogIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';
    public string $logName = 'all';
    public string $event = 'all';
    public ?int $clientId = null;
    public ?int $userId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingLogName(): void
    {
        $this->resetPage();
    }

    public function updatingEvent(): void
    {
        $this->resetPage();
    }

    public function updatingClientId(): void
    {
        $this->resetPage();
    }

    public function updatingUserId(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = ActivityLog::query()
            ->with(['causer', 'subject', 'user'])
            ->when($this->search, fn ($q) => $q->where('description', 'like', '%' . $this->search . '%'))
            ->when($this->logName !== 'all', fn ($q) => $q->where('log_name', $this->logName))
            ->when($this->event !== 'all', fn ($q) => $q->where('event', $this->event))
            ->when($this->clientId, fn ($q) => $q->where('client_id', $this->clientId))
            ->when($this->userId, fn ($q) => $q->where('user_id', $this->userId))
            ->latest();

        return view('livewire.admin.activity-log-index', [
            'activities' => $query->paginate(50),
            'logNames' => ActivityLog::query()->select('log_name')->distinct()->pluck('log_name')->filter()->values(),
            'events' => ActivityLog::query()->select('event')->distinct()->pluck('event')->filter()->values(),
        ]);
    }
}

