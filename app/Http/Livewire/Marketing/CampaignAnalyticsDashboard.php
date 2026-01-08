<?php

namespace App\Http\Livewire\Marketing;

use App\Models\Campaign;
use App\Models\CampaignMetric;
use App\Models\Client;
use App\Models\MarketingMetric;
use App\Services\Marketing\MarketingAnalyticsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CampaignAnalyticsDashboard extends Component
{
    public $clientId;
    public $client;
    public $selectedCampaignId = 'all';
    public $dateRange = '30';
    public $selectedMetric = 'impressions';

    protected $queryString = [
        'selectedCampaignId' => ['except' => 'all'],
        'dateRange' => ['except' => '30'],
    ];

    public function mount($clientId = null)
    {
        $this->clientId = $clientId ?? auth()->user()->client_id;
        $this->client = Client::findOrFail($this->clientId);
    }

    public function generateInsights()
    {
        try {
            $startDate = $this->getStartDate();
            $endDate = now();

            $service = new MarketingAnalyticsService();
            $insights = $service->generateInsights($this->clientId, $startDate, $endDate);

            session()->flash('insights', $insights);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to generate insights: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $startDate = $this->getStartDate();

        $campaigns = Campaign::where('client_id', $this->clientId)
            ->orderBy('start_date', 'desc')
            ->get();

        $stats = $this->getOverallStats($startDate);
        $chartData = $this->getChartData($startDate);
        $topPerformers = $this->getTopPerformingCampaigns($startDate);
        $channelBreakdown = $this->getChannelBreakdown($startDate);

        return view('livewire.marketing.campaign-analytics-dashboard', [
            'campaigns' => $campaigns,
            'stats' => $stats,
            'chartData' => $chartData,
            'topPerformers' => $topPerformers,
            'channelBreakdown' => $channelBreakdown,
        ]);
    }

    protected function getStartDate(): Carbon
    {
        return match ($this->dateRange) {
            '7' => now()->subDays(7),
            '30' => now()->subDays(30),
            '90' => now()->subDays(90),
            '365' => now()->subDays(365),
            default => now()->subDays(30),
        };
    }

    protected function getOverallStats(Carbon $startDate): array
    {
        $query = CampaignMetric::whereHas('campaign', function ($q) {
            $q->where('client_id', $this->clientId);
        })->where('date', '>=', $startDate);

        if ($this->selectedCampaignId !== 'all') {
            $query->where('campaign_id', $this->selectedCampaignId);
        }

        $totals = $query->select([
            DB::raw('SUM(impressions) as total_impressions'),
            DB::raw('SUM(clicks) as total_clicks'),
            DB::raw('SUM(conversions) as total_conversions'),
            DB::raw('SUM(spend) as total_spend'),
            DB::raw('SUM(revenue) as total_revenue'),
        ])->first();

        $ctr = $totals->total_impressions > 0
            ? ($totals->total_clicks / $totals->total_impressions) * 100
            : 0;

        $conversionRate = $totals->total_clicks > 0
            ? ($totals->total_conversions / $totals->total_clicks) * 100
            : 0;

        $roi = $totals->total_spend > 0
            ? (($totals->total_revenue - $totals->total_spend) / $totals->total_spend) * 100
            : 0;

        $cpc = $totals->total_clicks > 0
            ? $totals->total_spend / $totals->total_clicks
            : 0;

        $cpa = $totals->total_conversions > 0
            ? $totals->total_spend / $totals->total_conversions
            : 0;

        return [
            'impressions' => $totals->total_impressions ?? 0,
            'clicks' => $totals->total_clicks ?? 0,
            'conversions' => $totals->total_conversions ?? 0,
            'spend' => $totals->total_spend ?? 0,
            'revenue' => $totals->total_revenue ?? 0,
            'ctr' => $ctr,
            'conversion_rate' => $conversionRate,
            'roi' => $roi,
            'cpc' => $cpc,
            'cpa' => $cpa,
        ];
    }

    protected function getChartData(Carbon $startDate): array
    {
        $query = CampaignMetric::whereHas('campaign', function ($q) {
            $q->where('client_id', $this->clientId);
        })->where('date', '>=', $startDate);

        if ($this->selectedCampaignId !== 'all') {
            $query->where('campaign_id', $this->selectedCampaignId);
        }

        return $query->select(
            'date',
            DB::raw('SUM(impressions) as impressions'),
            DB::raw('SUM(clicks) as clicks'),
            DB::raw('SUM(conversions) as conversions'),
            DB::raw('SUM(spend) as spend'),
            DB::raw('SUM(revenue) as revenue')
        )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    protected function getTopPerformingCampaigns(Carbon $startDate, int $limit = 5): array
    {
        return Campaign::where('client_id', $this->clientId)
            ->with(['metrics' => function ($query) use ($startDate) {
                $query->where('date', '>=', $startDate);
            }])
            ->get()
            ->map(function ($campaign) {
                $metrics = $campaign->metrics;

                $totalImpressions = $metrics->sum('impressions');
                $totalClicks = $metrics->sum('clicks');
                $totalConversions = $metrics->sum('conversions');
                $totalSpend = $metrics->sum('spend');
                $totalRevenue = $metrics->sum('revenue');

                $roi = $totalSpend > 0
                    ? (($totalRevenue - $totalSpend) / $totalSpend) * 100
                    : 0;

                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'type' => $campaign->type,
                    'impressions' => $totalImpressions,
                    'clicks' => $totalClicks,
                    'conversions' => $totalConversions,
                    'spend' => $totalSpend,
                    'revenue' => $totalRevenue,
                    'roi' => $roi,
                ];
            })
            ->sortByDesc('roi')
            ->take($limit)
            ->values()
            ->toArray();
    }

    protected function getChannelBreakdown(Carbon $startDate): array
    {
        $query = CampaignMetric::whereHas('campaign', function ($q) {
            $q->where('client_id', $this->clientId);
        })->where('date', '>=', $startDate);

        if ($this->selectedCampaignId !== 'all') {
            $query->where('campaign_id', $this->selectedCampaignId);
        }

        return $query->select(
            'channel',
            DB::raw('SUM(impressions) as impressions'),
            DB::raw('SUM(clicks) as clicks'),
            DB::raw('SUM(conversions) as conversions'),
            DB::raw('SUM(spend) as spend'),
            DB::raw('SUM(revenue) as revenue')
        )
            ->groupBy('channel')
            ->orderByDesc('spend')
            ->get()
            ->toArray();
    }
}
