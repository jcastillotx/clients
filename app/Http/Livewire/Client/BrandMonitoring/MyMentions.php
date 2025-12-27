<?php

namespace App\Http\Livewire\Client\BrandMonitoring;

use App\Models\BrandMention;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MyMentions extends Component
{
    use WithPagination;

    public $selectedPlatform = '';

    public $selectedSentiment = '';

    public $dateFrom = '';

    public $dateTo = '';

    public $searchTerm = '';

    protected $queryString = [
        'selectedPlatform' => ['except' => ''],
        'selectedSentiment' => ['except' => ''],
        'searchTerm' => ['except' => ''],
    ];

    public function mount()
    {
        abort_unless(Auth::user()?->isClient(), 403);

        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function updated($propertyName)
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->selectedPlatform = '';
        $this->selectedSentiment = '';
        $this->searchTerm = '';
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function render()
    {
        $user = Auth::user();
        $clientId = $user->client_id;

        $query = BrandMention::where('client_id', $clientId)
            ->when($this->selectedPlatform, fn ($q) => $q->where('platform', $this->selectedPlatform))
            ->when($this->selectedSentiment, fn ($q) => $q->where('sentiment', $this->selectedSentiment))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('posted_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('posted_at', '<=', $this->dateTo))
            ->when($this->searchTerm, fn ($q) => $q->where(function ($query) {
                $query->where('mention_text', 'like', '%'.$this->searchTerm.'%')
                    ->orWhere('author', 'like', '%'.$this->searchTerm.'%');
            }))
            ->orderByDesc('posted_at');

        $mentions = $query->paginate(15);

        // Quick stats
        $totalMentions = BrandMention::where('client_id', $clientId)
            ->whereBetween('posted_at', [$this->dateFrom, $this->dateTo.' 23:59:59'])
            ->count();

        $sentimentBreakdown = BrandMention::where('client_id', $clientId)
            ->whereBetween('posted_at', [$this->dateFrom, $this->dateTo.' 23:59:59'])
            ->selectRaw('sentiment, COUNT(*) as count')
            ->groupBy('sentiment')
            ->pluck('count', 'sentiment')
            ->toArray();

        $recentPositive = BrandMention::where('client_id', $clientId)
            ->where('sentiment', 'positive')
            ->whereBetween('posted_at', [$this->dateFrom, $this->dateTo.' 23:59:59'])
            ->count();

        $recentNegative = BrandMention::where('client_id', $clientId)
            ->where('sentiment', 'negative')
            ->whereBetween('posted_at', [$this->dateFrom, $this->dateTo.' 23:59:59'])
            ->count();

        $platforms = ['news', 'google_news', 'yelp', 'google', 'reddit', 'youtube', 'x', 'web'];

        return view('livewire.client.brand-monitoring.my-mentions', [
            'mentions' => $mentions,
            'totalMentions' => $totalMentions,
            'sentimentBreakdown' => $sentimentBreakdown,
            'recentPositive' => $recentPositive,
            'recentNegative' => $recentNegative,
            'platforms' => $platforms,
        ]);
    }
}
