<?php

namespace App\Http\Livewire\Admin\Requests;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Request as ServiceRequest;
use App\Models\RequestComment;
use App\Models\User;
use App\Notifications\RequestAssignedNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;
use Livewire\WithPagination;

class AdminRequestManagement extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $viewMode = 'table'; // table|kanban

    public string $search = '';

    public ?int $clientId = null;

    public string $clientSearch = '';

    /** @var array<int, string> */
    public array $statuses = [];

    /** @var array<int, string> */
    public array $types = [];

    public string $priority = '';

    public ?int $assignedTo = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    /** @var array<int, int> */
    public array $selected = [];

    public bool $selectPage = false;

    // Bulk actions
    public string $bulkStatus = '';

    public string $bulkPriority = '';

    public ?int $bulkAssignedTo = null;

    // Assignment modal state
    public bool $showAssign = false;

    public ?int $assignRequestId = null;

    public ?int $assignToUserId = null;

    public ?string $assignDueDate = null; // YYYY-MM-DD

    public string $assignInternalNote = '';

    public bool $assignNotify = true;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingClientId(): void
    {
        $this->resetPage();
    }

    public function updatingStatuses(): void
    {
        $this->resetPage();
    }

    public function updatingTypes(): void
    {
        $this->resetPage();
    }

    public function updatingPriority(): void
    {
        $this->resetPage();
    }

    public function updatingAssignedTo(): void
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

    public function updatingViewMode(): void
    {
        $this->resetPage();
    }

    protected function baseQuery(): Builder
    {
        $user = auth()->user();
        $staffClientIds = [];
        if ($user && $user->hasRole('staff') && ! $user->hasAnyRole(['super_admin', 'admin'])) {
            $staffClientIds = $user->assignedClientIds();
        }

        return ServiceRequest::query()
            ->with(['client', 'creator', 'assignee'])
            ->when(! empty($staffClientIds), fn ($q) => $q->whereIn('client_id', $staffClientIds))
            ->when($this->search, function ($q) {
                $s = '%'.$this->search.'%';
                $q->where(function ($qq) use ($s) {
                    $qq->where('title', 'like', $s)
                        ->orWhere('description', 'like', $s)
                        ->orWhereHas('client', fn ($c) => $c->where('company_name', 'like', $s));
                });
            })
            ->when($this->clientId, fn ($q) => $q->where('client_id', $this->clientId))
            ->when(! empty($this->statuses), fn ($q) => $q->whereIn('status', $this->statuses))
            ->when(! empty($this->types), fn ($q) => $q->whereIn('type', $this->types))
            ->when($this->priority !== '' && $this->priority !== 'all', fn ($q) => $q->where('priority', $this->priority))
            ->when($this->assignedTo, fn ($q) => $q->where('assigned_to', $this->assignedTo))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderByDesc('created_at');
    }

    public function updatedSelectPage(bool $value): void
    {
        if (! $value) {
            $this->selected = [];

            return;
        }

        $this->selected = $this->baseQuery()
            ->limit(100)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function applyBulkStatus(): void
    {
        if (empty($this->selected) || $this->bulkStatus === '') {
            return;
        }

        ServiceRequest::query()
            ->whereIn('id', $this->selected)
            ->update(['status' => $this->bulkStatus]);

        session()->flash('success', 'Bulk status updated.');
        $this->selectPage = false;
        $this->selected = [];
        $this->bulkStatus = '';
    }

    public function applyBulkPriority(): void
    {
        if (empty($this->selected) || $this->bulkPriority === '') {
            return;
        }

        ServiceRequest::query()
            ->whereIn('id', $this->selected)
            ->update(['priority' => $this->bulkPriority]);

        session()->flash('success', 'Bulk priority updated.');
        $this->selectPage = false;
        $this->selected = [];
        $this->bulkPriority = '';
    }

    public function applyBulkAssign(): void
    {
        if (empty($this->selected) || ! $this->bulkAssignedTo) {
            return;
        }

        ServiceRequest::query()
            ->whereIn('id', $this->selected)
            ->update(['assigned_to' => $this->bulkAssignedTo]);

        session()->flash('success', 'Bulk assignment updated.');
        $this->selectPage = false;
        $this->selected = [];
        $this->bulkAssignedTo = null;
    }

    public function openAssign(int $requestId): void
    {
        $req = ServiceRequest::query()->findOrFail($requestId);
        $this->assignRequestId = $req->id;
        $this->assignToUserId = $req->assigned_to;
        $this->assignDueDate = $req->due_date?->format('Y-m-d');
        $this->assignInternalNote = '';
        $this->assignNotify = true;
        $this->showAssign = true;
    }

    public function saveAssignment(): void
    {
        if (! $this->assignRequestId) {
            return;
        }
        $req = ServiceRequest::query()->with('client')->findOrFail($this->assignRequestId);

        $req->update([
            'assigned_to' => $this->assignToUserId,
            'due_date' => $this->assignDueDate ?: null,
        ]);

        if (trim($this->assignInternalNote) !== '') {
            RequestComment::create([
                'request_id' => $req->id,
                'user_id' => auth()->id(),
                'comment' => trim($this->assignInternalNote),
                'is_internal' => true,
            ]);
        }

        ActivityLog::log(
            "Assigned request: {$req->title}",
            $req,
            ['assigned_to' => $this->assignToUserId, 'due_date' => $this->assignDueDate],
            'assigned',
            'requests'
        );

        if ($this->assignNotify && $this->assignToUserId) {
            $staff = User::query()->find($this->assignToUserId);
            if ($staff) {
                Notification::send($staff, new RequestAssignedNotification($req->load('client')));
            }
        }

        $this->showAssign = false;
        session()->flash('success', 'Assignment updated.');
    }

    public function moveRequest(int $requestId, string $status): void
    {
        $allowed = array_keys(config('client-portal.request_statuses', []));
        if (! in_array($status, $allowed, true)) {
            return;
        }

        $req = ServiceRequest::query()->findOrFail($requestId);

        $updates = ['status' => $status];
        if ($status === 'in_progress' && ! $req->started_at) {
            $updates['started_at'] = now();
        }
        if ($status === 'completed' && ! $req->completed_at) {
            $updates['completed_at'] = now();
        }

        $req->update($updates);
    }

    public function getClientOptionsProperty(): array
    {
        $q = Client::query()->orderBy('company_name');
        if (trim($this->clientSearch) !== '') {
            $s = '%'.trim($this->clientSearch).'%';
            $q->where('company_name', 'like', $s);
        }

        return $q->limit(25)->get(['id', 'company_name'])->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->company_name,
        ])->toArray();
    }

    public function getStaffOptionsProperty(): array
    {
        return User::query()
            ->role(['super_admin', 'admin', 'staff'])
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])
            ->toArray();
    }

    public function render()
    {
        $statuses = config('client-portal.request_statuses', []);
        $types = config('client-portal.request_types', []);
        $priorities = config('client-portal.request_priorities', []);

        if ($this->viewMode === 'kanban') {
            $columns = ! empty($this->statuses) ? $this->statuses : array_keys($statuses);
            $boards = [];
            foreach ($columns as $status) {
                $boards[$status] = (clone $this->baseQuery())
                    ->where('status', $status)
                    ->limit(50)
                    ->get();
            }

            return view('livewire.admin.requests.kanban', [
                'columns' => $columns,
                'statusLabels' => $statuses,
                'boards' => $boards,
                'types' => $types,
                'priorities' => $priorities,
                'clientOptions' => $this->clientOptions,
                'staffOptions' => $this->staffOptions,
            ])->layout('layouts.admin', ['title' => 'Requests']);
        }

        return view('livewire.admin.requests.index', [
            'requests' => $this->baseQuery()->paginate(20),
            'statusLabels' => $statuses,
            'types' => $types,
            'priorities' => $priorities,
            'clientOptions' => $this->clientOptions,
            'staffOptions' => $this->staffOptions,
        ])->layout('layouts.admin', ['title' => 'Requests']);
    }
}
