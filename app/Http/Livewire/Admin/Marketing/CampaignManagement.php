<?php

namespace App\Http\Livewire\Admin\Marketing;

use App\Models\Campaign;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class CampaignManagement extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    // Filters
    public string $search = '';
    public string $statusFilter = 'all';
    public string $typeFilter = 'all';
    public ?int $clientFilter = null;

    // Form fields
    public bool $showForm = false;
    public ?int $editingCampaignId = null;
    public ?int $clientId = null;
    public string $campaignName = '';
    public string $campaignType = 'content';
    public string $description = '';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?float $budget = null;
    public string $status = 'planning';
    public array $goals = [];
    public string $newGoal = '';

    // Delete confirmation
    public bool $showDeleteModal = false;
    public ?int $deletingCampaignId = null;

    // Bulk operations
    public array $selectedCampaigns = [];
    public bool $selectAll = false;
    public bool $showBulkModal = false;
    public string $bulkAction = '';
    public string $bulkStatus = 'active';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
        'typeFilter' => ['except' => 'all'],
        'clientFilter' => ['except' => null],
    ];

    protected $listeners = ['refreshCampaigns' => '$refresh'];

    protected function rules(): array
    {
        return [
            'clientId' => 'required|exists:clients,id',
            'campaignName' => 'required|string|max:255',
            'campaignType' => 'required|in:social,email,ppc,content,seo,launch,event,seasonal',
            'description' => 'nullable|string|max:1000',
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date|after_or_equal:startDate',
            'budget' => 'nullable|numeric|min:0',
            'status' => 'required|in:planning,active,paused,completed',
            'goals' => 'array',
        ];
    }

    protected $messages = [
        'clientId.required' => 'Please select a client.',
        'campaignName.required' => 'Campaign name is required.',
        'endDate.after_or_equal' => 'End date must be after or equal to start date.',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingClientFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedCampaigns = $this->campaigns->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selectedCampaigns = [];
        }
    }

    public function openForm(?int $campaignId = null): void
    {
        $this->resetForm();

        if ($campaignId) {
            $campaign = Campaign::findOrFail($campaignId);
            $this->editingCampaignId = $campaign->id;
            $this->clientId = $campaign->client_id;
            $this->campaignName = $campaign->campaign_name;
            $this->campaignType = $campaign->campaign_type;
            $this->description = $campaign->description ?? '';
            $this->startDate = $campaign->start_date?->format('Y-m-d');
            $this->endDate = $campaign->end_date?->format('Y-m-d');
            $this->budget = $campaign->budget;
            $this->status = $campaign->status;
            $this->goals = $campaign->goals ?? [];
        }

        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function saveCampaign(): void
    {
        $this->validate();

        $data = [
            'client_id' => $this->clientId,
            'campaign_name' => $this->campaignName,
            'campaign_type' => $this->campaignType,
            'description' => $this->description ?: null,
            'start_date' => $this->startDate ?: null,
            'end_date' => $this->endDate ?: null,
            'budget' => $this->budget,
            'status' => $this->status,
            'goals' => $this->goals,
            'created_by' => Auth::id(),
        ];

        if ($this->editingCampaignId) {
            $campaign = Campaign::findOrFail($this->editingCampaignId);
            $campaign->update($data);
            session()->flash('success', 'Campaign updated successfully!');
        } else {
            Campaign::create($data);
            session()->flash('success', 'Campaign created successfully!');
        }

        $this->closeForm();
        $this->dispatch('refreshCampaigns');
    }

    public function addGoal(): void
    {
        if (trim($this->newGoal) !== '') {
            $this->goals[] = trim($this->newGoal);
            $this->newGoal = '';
        }
    }

    public function removeGoal(int $index): void
    {
        if (isset($this->goals[$index])) {
            unset($this->goals[$index]);
            $this->goals = array_values($this->goals);
        }
    }

    public function confirmDelete(int $campaignId): void
    {
        $this->deletingCampaignId = $campaignId;
        $this->showDeleteModal = true;
    }

    public function deleteCampaign(): void
    {
        if ($this->deletingCampaignId) {
            Campaign::destroy($this->deletingCampaignId);
            session()->flash('success', 'Campaign deleted successfully!');
            $this->dispatch('refreshCampaigns');
        }

        $this->showDeleteModal = false;
        $this->deletingCampaignId = null;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingCampaignId = null;
    }

    public function duplicateCampaign(int $campaignId): void
    {
        $campaign = Campaign::findOrFail($campaignId);

        Campaign::create([
            'client_id' => $campaign->client_id,
            'campaign_name' => $campaign->campaign_name . ' (Copy)',
            'campaign_type' => $campaign->campaign_type,
            'description' => $campaign->description,
            'start_date' => null,
            'end_date' => null,
            'budget' => $campaign->budget,
            'status' => 'planning',
            'goals' => $campaign->goals,
            'target_metrics' => $campaign->target_metrics,
            'created_by' => Auth::id(),
        ]);

        session()->flash('success', 'Campaign duplicated successfully!');
        $this->dispatch('refreshCampaigns');
    }

    public function openBulkModal(): void
    {
        if (count($this->selectedCampaigns) === 0) {
            session()->flash('error', 'Please select at least one campaign.');
            return;
        }
        $this->showBulkModal = true;
    }

    public function closeBulkModal(): void
    {
        $this->showBulkModal = false;
        $this->bulkAction = '';
        $this->bulkStatus = 'active';
    }

    public function executeBulkAction(): void
    {
        if (count($this->selectedCampaigns) === 0) {
            session()->flash('error', 'No campaigns selected.');
            $this->closeBulkModal();
            return;
        }

        $count = count($this->selectedCampaigns);

        switch ($this->bulkAction) {
            case 'status':
                Campaign::whereIn('id', $this->selectedCampaigns)
                    ->update(['status' => $this->bulkStatus]);
                session()->flash('success', "{$count} campaign(s) status updated to {$this->bulkStatus}.");
                break;

            case 'delete':
                Campaign::whereIn('id', $this->selectedCampaigns)->delete();
                session()->flash('success', "{$count} campaign(s) deleted.");
                break;
        }

        $this->selectedCampaigns = [];
        $this->selectAll = false;
        $this->closeBulkModal();
        $this->dispatch('refreshCampaigns');
    }

    public function resetForm(): void
    {
        $this->editingCampaignId = null;
        $this->clientId = null;
        $this->campaignName = '';
        $this->campaignType = 'content';
        $this->description = '';
        $this->startDate = null;
        $this->endDate = null;
        $this->budget = null;
        $this->status = 'planning';
        $this->goals = [];
        $this->newGoal = '';
        $this->resetErrorBag();
    }

    public function getCampaignsProperty()
    {
        $query = Campaign::with(['client', 'creator']);

        if ($this->search) {
            $search = '%' . $this->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('campaign_name', 'like', $search)
                    ->orWhere('description', 'like', $search)
                    ->orWhereHas('client', function ($cq) use ($search) {
                        $cq->where('company_name', 'like', $search);
                    });
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->typeFilter !== 'all') {
            $query->where('campaign_type', $this->typeFilter);
        }

        if ($this->clientFilter) {
            $query->where('client_id', $this->clientFilter);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    public function getClientsProperty()
    {
        return Client::orderBy('company_name')->get(['id', 'company_name']);
    }

    public function getStatsProperty(): array
    {
        $query = Campaign::query();

        if ($this->clientFilter) {
            $query->where('client_id', $this->clientFilter);
        }

        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->where('status', 'active')->count(),
            'planning' => (clone $query)->where('status', 'planning')->count(),
            'paused' => (clone $query)->where('status', 'paused')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'total_budget' => (clone $query)->sum('budget') ?? 0,
        ];
    }

    public function getStatusBadgeClass(string $status): string
    {
        return match ($status) {
            'active' => 'badge-success',
            'paused' => 'badge-warning',
            'planning' => 'badge-info',
            'completed' => 'badge-secondary',
            default => 'badge-light',
        };
    }

    public function getTypeBadgeClass(string $type): string
    {
        return match ($type) {
            'social' => 'badge-pink',
            'email' => 'badge-purple',
            'ppc' => 'badge-orange',
            'content' => 'badge-teal',
            'seo' => 'badge-indigo',
            'launch' => 'badge-danger',
            'event' => 'badge-cyan',
            'seasonal' => 'badge-warning',
            default => 'badge-secondary',
        };
    }

    public function render()
    {
        return view('livewire.admin.marketing.campaign-management', [
            'campaigns' => $this->campaigns,
            'clients' => $this->clients,
            'stats' => $this->stats,
        ])->layout('layouts.app', ['title' => 'Campaign Management']);
    }
}
