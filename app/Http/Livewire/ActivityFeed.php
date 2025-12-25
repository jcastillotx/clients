<?php

namespace App\Http\Livewire;

use App\Models\ActivityLog;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityFeed extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    /**
     * all|requests|invoices|contracts
     */
    public string $type = 'all';

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();
        $clientId = $user?->client_id;

        if (!$clientId) {
            return view('livewire.activity-feed', [
                'activities' => collect(),
            ]);
        }

        $query = ActivityLog::query()
            ->where('client_id', $clientId)
            ->with(['causer', 'subject', 'user'])
            ->latest();

        if ($this->type !== 'all') {
            $query->where('log_name', $this->type);
        }

        return view('livewire.activity-feed', [
            'activities' => $query->paginate(20),
        ]);
    }
}

