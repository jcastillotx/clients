<?php

namespace App\Http\Livewire\Admin\BrandMonitoring;

use App\Models\BrandMention;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Dashboard extends Component
{
    use WithPagination;

    public $selectedClient = null;

    public $selectedPlatform = '';

    public $selectedSentiment = '';

    public $dateFrom = '';

    public $dateTo = '';

    public $searchTerm = '';

    protected $queryString = [
        'selectedClient' => ['except' => null],
        'selectedPlatform' => ['except' => ''],
        'selectedSentiment' => ['except' => ''],
        'searchTerm' => ['except' => ''],
    ];

    public function mount()
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $this->dateFrom = now()->subDays(7)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function updated($propertyName)
    {
        $this->resetPage();
    }

    public $showResponseModal = false;
    public $respondingToMention = null;
    public $responseNotes = '';

    public function clearFilters()
    {
        $this->selectedClient = null;
        $this->selectedPlatform = '';
        $this->selectedSentiment = '';
        $this->searchTerm = '';
        $this->dateFrom = now()->subDays(7)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function openResponseModal(int $mentionId)
    {
        $this->respondingToMention = BrandMention::find($mentionId);
        $this->responseNotes = '';
        $this->showResponseModal = true;
    }

    public function closeResponseModal()
    {
        $this->showResponseModal = false;
        $this->respondingToMention = null;
        $this->responseNotes = '';
    }

    public function markAsResponded()
    {
        if (!$this->respondingToMention) {
            return;
        }

        $this->respondingToMention->markAsResponded(
            Auth::id(),
            $this->responseNotes ?: null
        );

        session()->flash('success', 'Mention marked as responded.');
        $this->closeResponseModal();
    }

    public function render()
    {
        $query = BrandMention::with('client')
            ->when($this->selectedClient, fn ($q) => $q->where('client_id', $this->selectedClient))
            ->when($this->selectedPlatform, fn ($q) => $q->where('platform', $this->selectedPlatform))
            ->when($this->selectedSentiment, fn ($q) => $q->where('sentiment', $this->selectedSentiment))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('posted_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('posted_at', '<=', $this->dateTo))
            ->when($this->searchTerm, fn ($q) => $q->where(function ($query) {
                $query->where('mention_text', 'like', '%'.$this->searchTerm.'%')
                    ->orWhere('author', 'like', '%'.$this->searchTerm.'%');
            }))
            ->orderByDesc('posted_at');

        $mentions = $query->paginate(20);

        // Analytics
        $totalMentions = BrandMention::query()
            ->when($this->selectedClient, fn ($q) => $q->where('client_id', $this->selectedClient))
            ->whereBetween('posted_at', [$this->dateFrom, $this->dateTo.' 23:59:59'])
            ->count();

        $sentimentBreakdown = BrandMention::query()
            ->when($this->selectedClient, fn ($q) => $q->where('client_id', $this->selectedClient))
            ->whereBetween('posted_at', [$this->dateFrom, $this->dateTo.' 23:59:59'])
            ->selectRaw('sentiment, COUNT(*) as count')
            ->groupBy('sentiment')
            ->pluck('count', 'sentiment')
            ->toArray();

        $platformBreakdown = BrandMention::query()
            ->when($this->selectedClient, fn ($q) => $q->where('client_id', $this->selectedClient))
            ->whereBetween('posted_at', [$this->dateFrom, $this->dateTo.' 23:59:59'])
            ->selectRaw('platform, COUNT(*) as count')
            ->groupBy('platform')
            ->orderByDesc('count')
            ->pluck('count', 'platform')
            ->toArray();

        $clients = Client::active()
            ->orderBy('company_name')
            ->get(['id', 'company_name']);

        $platforms = ['news', 'google_news', 'yelp', 'google', 'reddit', 'youtube', 'x', 'web'];

        // Count negative mentions that need response
        $needsAttentionCount = BrandMention::query()
            ->when($this->selectedClient, fn ($q) => $q->where('client_id', $this->selectedClient))
            ->where('sentiment', 'negative')
            ->whereNull('responded_at')
            ->whereBetween('posted_at', [$this->dateFrom, $this->dateTo.' 23:59:59'])
            ->count();

        return view('livewire.admin.brand-monitoring.dashboard', [
            'mentions' => $mentions,
            'totalMentions' => $totalMentions,
            'sentimentBreakdown' => $sentimentBreakdown,
            'platformBreakdown' => $platformBreakdown,
            'clients' => $clients,
            'platforms' => $platforms,
            'needsAttentionCount' => $needsAttentionCount,
        ])->layout('layouts.admin');
    }
}
