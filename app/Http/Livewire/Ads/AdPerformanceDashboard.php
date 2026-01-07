<?php

namespace App\Http\Livewire\Ads;

use App\Models\AdCampaign;
use App\Models\AdMetric;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AdPerformanceDashboard extends Component
{
    public $clientId;
    public $client;
    public $selectedCampaignId = 'all';
    public $dateRange = '30';

    protected $queryString = [
        'selectedCampaignId' => ['except' => 'all'],
        'dateRange' => ['except' => '30'],
    ];

    public function mount($clientId = null)
    {
        $this->clientId = $clientId ?? auth()->user()->client_id;
        $this->client = Client::findOrFail($this->clientId);
    }

    public function render()
    {
        $startDate = $this->getStartDate();

        $campaigns = AdCampaign::where('client_id', $this->clientId)
            ->with('adAccount')
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = $this->getOverallStats($startDate);
        $chartData = $this->getChartData($startDate);
        $topCampaigns = $this->getTopCampaigns($startDate);
        $platformBreakdown = $this->getPlatformBreakdown($startDate);

        return view('livewire.ads.ad-performance-dashboard', [
            'campaigns' => $campaigns,
            'stats' => $stats,
            'chartData' => $chartData,
            'topCampaigns' => $topCampaigns,
            'platformBreakdown' => $platformBreakdown,
        ]);
    }

    protected function getStartDate(): Carbon
    {
        return match ($this->dateRange) {
            '7' => now()->subDays(7),
            '30' => now()->subDays(30),
            '90' => now()->subDays(90),
            default => now()->subDays(30),
        };
    }

    protected function getOverallStats(Carbon $startDate): array
    {
        $query = AdMetric::whereHas('entity', function ($q) {
            if ($q instanceof \Illuminate\Database\Eloquent\Builder) {
                $q->where('client_id', $this->clientId);
            }
        })
            ->where('date', '>=', $startDate)
            ->where('entity_type', 'campaign');

        if ($this->selectedCampaignId !== 'all') {
            $query->where('entity_id', $this->selectedCampaignId);
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

        $cpc = $totals->total_clicks > 0
            ? $totals->total_spend / $totals->total_clicks
            : 0;

        $cpa = $totals->total_conversions > 0
            ? $totals->total_spend / $totals->total_conversions
            : 0;

        $roas = $totals->total_spend > 0
            ? $totals->total_revenue / $totals->total_spend
            : 0;

        return [
            'impressions' => $totals->total_impressions ?? 0,
            'clicks' => $totals->total_clicks ?? 0,
            'conversions' => $totals->total_conversions ?? 0,
            'spend' => $totals->total_spend ?? 0,
            'revenue' => $totals->total_revenue ?? 0,
            'ctr' => $ctr,
            'conversion_rate' => $conversionRate,
            'cpc' => $cpc,
            'cpa' => $cpa,
            'roas' => $roas,
        ];
    }

    protected function getChartData(Carbon $startDate): array
    {
        $query = AdMetric::where('entity_type', 'campaign')
            ->where('date', '>=', $startDate)
            ->whereIn('entity_id', function ($query) {
                $query->select('id')
                    ->from('ad_campaigns')
                    ->where('client_id', $this->clientId);
            });

        if ($this->selectedCampaignId !== 'all') {
            $query->where('entity_id', $this->selectedCampaignId);
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

    protected function getTopCampaigns(Carbon $startDate, int $limit = 5): array
    {
        return AdCampaign::where('client_id', $this->clientId)
            ->with(['adAccount'])
            ->get()
            ->map(function ($campaign) use ($startDate) {
                $metrics = $campaign->metrics()
                    ->where('date', '>=', $startDate)
                    ->select([
                        DB::raw('SUM(impressions) as total_impressions'),
                        DB::raw('SUM(clicks) as total_clicks'),
                        DB::raw('SUM(conversions) as total_conversions'),
                        DB::raw('SUM(spend) as total_spend'),
                        DB::raw('SUM(revenue) as total_revenue'),
                    ])
                    ->first();

                $roas = $metrics->total_spend > 0
                    ? $metrics->total_revenue / $metrics->total_spend
                    : 0;

                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'platform' => $campaign->adAccount->platform_display_name,
                    'platform_color' => $campaign->adAccount->platform_color,
                    'status' => $campaign->status,
                    'impressions' => $metrics->total_impressions ?? 0,
                    'clicks' => $metrics->total_clicks ?? 0,
                    'conversions' => $metrics->total_conversions ?? 0,
                    'spend' => $metrics->total_spend ?? 0,
                    'revenue' => $metrics->total_revenue ?? 0,
                    'roas' => $roas,
                ];
            })
            ->sortByDesc('roas')
            ->take($limit)
            ->values()
            ->toArray();
    }

    protected function getPlatformBreakdown(Carbon $startDate): array
    {
        return AdCampaign::where('client_id', $this->clientId)
            ->with('adAccount')
            ->get()
            ->groupBy('ad_account_id')
            ->map(function ($campaigns, $accountId) use ($startDate) {
                $account = $campaigns->first()->adAccount;
                $campaignIds = $campaigns->pluck('id');

                $metrics = AdMetric::where('entity_type', 'campaign')
                    ->whereIn('entity_id', $campaignIds)
                    ->where('date', '>=', $startDate)
                    ->select([
                        DB::raw('SUM(impressions) as total_impressions'),
                        DB::raw('SUM(clicks) as total_clicks'),
                        DB::raw('SUM(conversions) as total_conversions'),
                        DB::raw('SUM(spend) as total_spend'),
                        DB::raw('SUM(revenue) as total_revenue'),
                    ])
                    ->first();

                return [
                    'platform' => $account->platform_display_name,
                    'platform_color' => $account->platform_color,
                    'impressions' => $metrics->total_impressions ?? 0,
                    'clicks' => $metrics->total_clicks ?? 0,
                    'conversions' => $metrics->total_conversions ?? 0,
                    'spend' => $metrics->total_spend ?? 0,
                    'revenue' => $metrics->total_revenue ?? 0,
                ];
            })
            ->values()
            ->toArray();
    }
}
