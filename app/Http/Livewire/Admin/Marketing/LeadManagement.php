<?php

namespace App\Http\Livewire\Admin\Marketing;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class LeadManagement extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $statusFilter = 'all';

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $company = '';

    public string $source = '';

    public string $status = 'new';

    public ?int $score = null;

    public ?int $assignedTo = null;

    /** @var array<string, string> */
    public array $statusOptions = [
        'new' => 'New',
        'contacted' => 'Contacted',
        'qualified' => 'Qualified',
        'won' => 'Won',
        'lost' => 'Lost',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:'.implode(',', array_keys($this->statusOptions))],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'assignedTo' => ['nullable', 'exists:users,id'],
        ];
    }

    protected function baseQuery(): Builder
    {
        return Lead::query()
            ->with('assignee')
            ->when($this->search, function (Builder $query) {
                $search = '%'.$this->search.'%';
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('company', 'like', $search)
                        ->orWhere('source', 'like', $search);
                });
            })
            ->when($this->statusFilter !== 'all', fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->orderByDesc('created_at');
    }

    public function createLead(): void
    {
        $validated = $this->validate();

        Lead::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'company' => $validated['company'],
            'source' => $validated['source'],
            'status' => $validated['status'],
            'score' => $validated['score'],
            'assigned_to' => $validated['assignedTo'],
        ]);

        $this->reset(['name', 'email', 'phone', 'company', 'source', 'status', 'score', 'assignedTo']);
        $this->status = 'new';

        session()->flash('success', 'Lead created successfully.');
    }

    public function render()
    {
        return view('livewire.admin.marketing.lead-management', [
            'leads' => $this->baseQuery()->paginate(10),
            'assignees' => User::query()->orderBy('name')->get(),
        ]);
    }
}
