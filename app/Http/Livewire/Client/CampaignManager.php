<?php

namespace App\Http\Livewire\Client;

use App\Models\Campaign;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class CampaignManager extends Component
{
    use WithPagination;

    public string $activeTab = 'list';
    public string $statusFilter = 'all';
    public string $typeFilter = 'all';
    public string $search = '';

    // Form fields
    public ?int $editingCampaignId = null;
    public string $campaignName = '';
    public string $campaignType = 'content';
    public string $description = '';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?float $budget = null;
    public string $status = 'planning';
    public array $goals = [];
    public string $newGoal = '';

    // Confirmation modal
    public bool $showDeleteModal = false;
    public ?int $deletingCampaignId = null;

    protected $queryString = [
        'activeTab' => ['except' => 'list'],
        'statusFilter' => ['except' => 'all'],
        'typeFilter' => ['except' => 'all'],
        'search' => ['except' => ''],
    ];

    protected $listeners = ['refreshCampaigns' => '$refresh'];

    protected function rules(): array
    {
        return [
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
        'campaignName.required' => 'Campaign name is required.',
        'endDate.after_or_equal' => 'End date must be after or equal to start date.',
    ];

    public function mount(): void
    {
        abort_unless(Auth::user()?->isClient(), 403);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        if ($tab === 'list') {
            $this->resetForm();
        }
        $this->resetPage();
    }

    public function createCampaign(): void
    {
        $this->resetForm();
        $this->activeTab = 'form';
    }

    public function editCampaign(int $campaignId): void
    {
        $campaign = Campaign::where('id', $campaignId)
            ->where('client_id', Auth::user()->client_id)
            ->firstOrFail();

        $this->editingCampaignId = $campaign->id;
        $this->campaignName = $campaign->campaign_name;
        $this->campaignType = $campaign->campaign_type;
        $this->description = $campaign->description ?? '';
        $this->startDate = $campaign->start_date?->format('Y-m-d');
        $this->endDate = $campaign->end_date?->format('Y-m-d');
        $this->budget = $campaign->budget;
        $this->status = $campaign->status;
        $this->goals = $campaign->goals ?? [];

        $this->activeTab = 'form';
    }

    public function saveCampaign(): void
    {
        $this->validate();

        $data = [
            'client_id' => Auth::user()->client_id,
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
            $campaign = Campaign::where('id', $this->editingCampaignId)
                ->where('client_id', Auth::user()->client_id)
                ->firstOrFail();

            $campaign->update($data);
            session()->flash('success', 'Campaign updated successfully!');
        } else {
            Campaign::create($data);
            session()->flash('success', 'Campaign created successfully!');
        }

        $this->resetForm();
        $this->activeTab = 'list';
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
            Campaign::where('id', $this->deletingCampaignId)
                ->where('client_id', Auth::user()->client_id)
                ->delete();

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
        $campaign = Campaign::where('id', $campaignId)
            ->where('client_id', Auth::user()->client_id)
            ->firstOrFail();

        Campaign::create([
            'client_id' => Auth::user()->client_id,
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

    public function resetForm(): void
    {
        $this->editingCampaignId = null;
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
        $query = Campaign::where('client_id', Auth::user()->client_id)
            ->with(['creator']);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->typeFilter !== 'all') {
            $query->where('campaign_type', $this->typeFilter);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('campaign_name', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate(10);
    }

    public function getStatsProperty(): array
    {
        $clientId = Auth::user()->client_id;
        $campaigns = Campaign::where('client_id', $clientId);

        return [
            'total' => (clone $campaigns)->count(),
            'active' => (clone $campaigns)->where('status', 'active')->count(),
            'planning' => (clone $campaigns)->where('status', 'planning')->count(),
            'completed' => (clone $campaigns)->where('status', 'completed')->count(),
            'total_budget' => (clone $campaigns)->sum('budget') ?? 0,
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
        return view('livewire.client.campaign-manager', [
            'campaigns' => $this->campaigns,
            'stats' => $this->stats,
        ])->layout('layouts.app', ['title' => 'Campaign Manager']);
    }
}
