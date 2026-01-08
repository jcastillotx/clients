<?php

namespace App\Http\Livewire\Marketing;

use App\Models\BrandMention;
use App\Models\Client;
use App\Services\BrandMonitoring\NewsMonitoringService;
use App\Services\BrandMonitoring\SentimentAnalysisService;
use App\Services\BrandMonitoring\SocialMonitoringService;
use App\Services\BrandMonitoring\WebMentionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class BrandMonitoringDashboard extends Component
{
    use WithPagination;

    public $clientId;
    public $client;
    public $dateRange = '7';
    public $platformFilter = 'all';
    public $sentimentFilter = 'all';
    public $searchTerm = '';

    protected $queryString = [
        'dateRange' => ['except' => '7'],
        'platformFilter' => ['except' => 'all'],
        'sentimentFilter' => ['except' => 'all'],
        'searchTerm' => ['except' => ''],
    ];

    public function mount($clientId = null)
    {
        $this->clientId = $clientId ?? auth()->user()->client_id;
        $this->client = Client::findOrFail($this->clientId);
    }

    public function fetchNewMentions()
    {
        try {
            $keywords = array_merge(
                [$this->client->company_name],
                explode(',', $this->client->meta['brand_keywords'] ?? '')
            );

            $newsService = new NewsMonitoringService();
            $socialService = new SocialMonitoringService();
            $webService = new WebMentionService();

            $newsService->fetchMentions($this->client, $keywords);
            $socialService->fetchMentions($this->client, $keywords);
            $webService->fetchMentions($this->client, $keywords);

            $sentimentService = new SentimentAnalysisService();
            $unanalyzedMentions = BrandMention::where('client_id', $this->clientId)
                ->whereNull('sentiment')
                ->get();

            if ($unanalyzedMentions->isNotEmpty()) {
                $sentimentService->analyzeBatch($unanalyzedMentions);
            }

            session()->flash('message', 'Successfully fetched new mentions and analyzed sentiment.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error fetching mentions: ' . $e->getMessage());
        }
    }

    public function markAsResponded($mentionId)
    {
        $mention = BrandMention::where('client_id', $this->clientId)->findOrFail($mentionId);
        $mention->update([
            'responded_at' => now(),
            'responded_by' => auth()->id(),
        ]);

        session()->flash('message', 'Mention marked as responded.');
    }

    public function render()
    {
        $startDate = $this->getStartDate();

        $mentions = BrandMention::where('client_id', $this->clientId)
            ->where('published_at', '>=', $startDate)
            ->when($this->platformFilter !== 'all', function ($query) {
                $query->where('platform', $this->platformFilter);
            })
            ->when($this->sentimentFilter !== 'all', function ($query) {
                $query->where('sentiment', $this->sentimentFilter);
            })
            ->when($this->searchTerm, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', '%' . $this->searchTerm . '%')
                        ->orWhere('content', 'like', '%' . $this->searchTerm . '%')
                        ->orWhere('author', 'like', '%' . $this->searchTerm . '%');
                });
            })
            ->orderBy('published_at', 'desc')
            ->paginate(20);

        $stats = $this->getStatistics($startDate);
        $sentimentTrend = $this->getSentimentTrend($startDate);
        $platformBreakdown = $this->getPlatformBreakdown($startDate);

        return view('livewire.marketing.brand-monitoring-dashboard', [
            'mentions' => $mentions,
            'stats' => $stats,
            'sentimentTrend' => $sentimentTrend,
            'platformBreakdown' => $platformBreakdown,
        ]);
    }

    protected function getStartDate(): Carbon
    {
        return match ($this->dateRange) {
            '1' => now()->subDay(),
            '7' => now()->subDays(7),
            '30' => now()->subDays(30),
            '90' => now()->subDays(90),
            default => now()->subDays(7),
        };
    }

    protected function getStatistics(Carbon $startDate): array
    {
        $query = BrandMention::where('client_id', $this->clientId)
            ->where('published_at', '>=', $startDate);

        return [
            'total_mentions' => $query->count(),
            'positive_mentions' => (clone $query)->where('sentiment', 'positive')->count(),
            'neutral_mentions' => (clone $query)->where('sentiment', 'neutral')->count(),
            'negative_mentions' => (clone $query)->where('sentiment', 'negative')->count(),
            'avg_sentiment_score' => (clone $query)->avg('sentiment_score') ?? 0,
            'responded_mentions' => (clone $query)->whereNotNull('responded_at')->count(),
            'unresponded_negative' => (clone $query)
                ->where('sentiment', 'negative')
                ->whereNull('responded_at')
                ->count(),
        ];
    }

    protected function getSentimentTrend(Carbon $startDate): array
    {
        return BrandMention::where('client_id', $this->clientId)
            ->where('published_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(published_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN sentiment = "positive" THEN 1 ELSE 0 END) as positive'),
                DB::raw('SUM(CASE WHEN sentiment = "neutral" THEN 1 ELSE 0 END) as neutral'),
                DB::raw('SUM(CASE WHEN sentiment = "negative" THEN 1 ELSE 0 END) as negative'),
                DB::raw('AVG(sentiment_score) as avg_score')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    protected function getPlatformBreakdown(Carbon $startDate): array
    {
        return BrandMention::where('client_id', $this->clientId)
            ->where('published_at', '>=', $startDate)
            ->select(
                'platform',
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(sentiment_score) as avg_sentiment')
            )
            ->groupBy('platform')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }
}
