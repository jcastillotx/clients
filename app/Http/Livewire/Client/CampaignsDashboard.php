<?php

namespace App\Http\Livewire\Client;

use App\Models\Campaign;
use App\Models\CampaignLink;
use App\Models\CampaignMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class CampaignsDashboard extends Component
{
    use WithPagination;

    public string $activeTab = 'overview';
    public string $statusFilter = 'all';
    public string $typeFilter = 'all';
    public string $dateRange = '30';
    public ?int $selectedCampaignId = null;

    // Campaign detail view data
    public array $campaignDetail = [];
    public array $campaignMetrics = [];
    public array $campaignLinks = [];

    protected $queryString = [
        'activeTab' => ['except' => 'overview'],
        'statusFilter' => ['except' => 'all'],
        'typeFilter' => ['except' => 'all'],
        'selectedCampaignId' => ['except' => null],
    ];

    protected $listeners = ['refreshCampaigns' => '$refresh'];

    public function mount(): void
    {
        abort_unless(Auth::user()?->isClient(), 403);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        if ($tab !== 'detail') {
            $this->selectedCampaignId = null;
        }
        $this->resetPage();
    }

    public function viewCampaign(int $campaignId): void
    {
        $this->selectedCampaignId = $campaignId;
        $this->activeTab = 'detail';
        $this->loadCampaignDetail();
    }

    public function loadCampaignDetail(): void
    {
        if (!$this->selectedCampaignId) {
            return;
        }

        $clientId = Auth::user()->client_id;
        $campaign = Campaign::where('id', $this->selectedCampaignId)
            ->where('client_id', $clientId)
            ->first();

        if (!$campaign) {
            $this->activeTab = 'overview';
            return;
        }

        $this->campaignDetail = [
            'id' => $campaign->id,
            'name' => $campaign->campaign_name,
            'type' => $campaign->campaign_type,
            'status' => $campaign->status,
            'description' => $campaign->description,
            'start_date' => $campaign->start_date?->format('M d, Y'),
            'end_date' => $campaign->end_date?->format('M d, Y'),
            'budget' => $campaign->budget,
            'goals' => $campaign->goals ?? [],
            'target_metrics' => $campaign->target_metrics ?? [],
            'created_at' => $campaign->created_at->format('M d, Y'),
        ];

        // Load metrics
        $startDate = now()->subDays((int) $this->dateRange);
        $metrics = CampaignMetric::where('campaign_id', $this->selectedCampaignId)
            ->where('metric_date', '>=', $startDate)
            ->select(
                DB::raw('SUM(impressions) as impressions'),
                DB::raw('SUM(clicks) as clicks'),
                DB::raw('SUM(conversions) as conversions'),
                DB::raw('SUM(spend) as spend'),
                DB::raw('SUM(revenue) as revenue')
            )
            ->first();

        $ctr = ($metrics->impressions ?? 0) > 0
            ? (($metrics->clicks ?? 0) / $metrics->impressions) * 100
            : 0;

        $conversionRate = ($metrics->clicks ?? 0) > 0
            ? (($metrics->conversions ?? 0) / $metrics->clicks) * 100
            : 0;

        $roi = ($metrics->spend ?? 0) > 0
            ? ((($metrics->revenue ?? 0) - $metrics->spend) / $metrics->spend) * 100
            : 0;

        $this->campaignMetrics = [
            'impressions' => $metrics->impressions ?? 0,
            'clicks' => $metrics->clicks ?? 0,
            'conversions' => $metrics->conversions ?? 0,
            'spend' => $metrics->spend ?? 0,
            'revenue' => $metrics->revenue ?? 0,
            'ctr' => round($ctr, 2),
            'conversion_rate' => round($conversionRate, 2),
            'roi' => round($roi, 1),
        ];

        // Load campaign links
        $this->campaignLinks = CampaignLink::where('campaign_id', $this->selectedCampaignId)
            ->orderBy('clicks', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($link) => [
                'id' => $link->id,
                'original_url' => $link->original_url,
                'short_url' => $link->short_url,
                'utm_source' => $link->utm_source,
                'utm_medium' => $link->utm_medium,
                'utm_campaign' => $link->utm_campaign,
                'clicks' => $link->clicks,
                'conversions' => $link->conversions,
            ])
            ->toArray();
    }

    public function getOverviewStatsProperty(): array
    {
        $clientId = Auth::user()->client_id;
        $startDate = now()->subDays((int) $this->dateRange);

        $campaigns = Campaign::where('client_id', $clientId)->get();

        $metrics = CampaignMetric::whereIn('campaign_id', $campaigns->pluck('id'))
            ->where('metric_date', '>=', $startDate)
            ->select(
                DB::raw('SUM(impressions) as impressions'),
                DB::raw('SUM(clicks) as clicks'),
                DB::raw('SUM(conversions) as conversions'),
                DB::raw('SUM(spend) as spend'),
                DB::raw('SUM(revenue) as revenue')
            )
            ->first();

        $activeCampaigns = $campaigns->where('status', 'active')->count();
        $totalBudget = $campaigns->sum('budget');

        $ctr = ($metrics->impressions ?? 0) > 0
            ? (($metrics->clicks ?? 0) / $metrics->impressions) * 100
            : 0;

        $roi = ($metrics->spend ?? 0) > 0
            ? ((($metrics->revenue ?? 0) - $metrics->spend) / $metrics->spend) * 100
            : 0;

        return [
            'total_campaigns' => $campaigns->count(),
            'active_campaigns' => $activeCampaigns,
            'total_budget' => $totalBudget ?? 0,
            'total_spend' => $metrics->spend ?? 0,
            'total_revenue' => $metrics->revenue ?? 0,
            'impressions' => $metrics->impressions ?? 0,
            'clicks' => $metrics->clicks ?? 0,
            'conversions' => $metrics->conversions ?? 0,
            'ctr' => round($ctr, 2),
            'roi' => round($roi, 1),
        ];
    }

    public function getCampaignsProperty()
    {
        $clientId = Auth::user()->client_id;

        $query = Campaign::where('client_id', $clientId)
            ->with(['creator']);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->typeFilter !== 'all') {
            $query->where('campaign_type', $this->typeFilter);
        }

        return $query->orderBy('created_at', 'desc')->paginate(10);
    }

    public function getStatusCountsProperty(): array
    {
        $clientId = Auth::user()->client_id;

        return Campaign::where('client_id', $clientId)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    public function getTypeCountsProperty(): array
    {
        $clientId = Auth::user()->client_id;

        return Campaign::where('client_id', $clientId)
            ->select('campaign_type', DB::raw('COUNT(*) as count'))
            ->groupBy('campaign_type')
            ->pluck('count', 'campaign_type')
            ->toArray();
    }

    public function getChartDataProperty(): array
    {
        $clientId = Auth::user()->client_id;
        $startDate = now()->subDays((int) $this->dateRange);

        $campaigns = Campaign::where('client_id', $clientId)->pluck('id');

        return CampaignMetric::whereIn('campaign_id', $campaigns)
            ->where('metric_date', '>=', $startDate)
            ->select(
                'metric_date',
                DB::raw('SUM(impressions) as impressions'),
                DB::raw('SUM(clicks) as clicks'),
                DB::raw('SUM(conversions) as conversions'),
                DB::raw('SUM(spend) as spend')
            )
            ->groupBy('metric_date')
            ->orderBy('metric_date')
            ->get()
            ->map(fn ($row) => [
                'date' => Carbon::parse($row->metric_date)->format('M d'),
                'impressions' => $row->impressions,
                'clicks' => $row->clicks,
                'conversions' => $row->conversions,
                'spend' => $row->spend,
            ])
            ->toArray();
    }

    public function getTopCampaignsProperty(): array
    {
        $clientId = Auth::user()->client_id;
        $startDate = now()->subDays((int) $this->dateRange);

        return Campaign::where('client_id', $clientId)
            ->where('status', 'active')
            ->with(['metrics' => fn ($q) => $q->where('metric_date', '>=', $startDate)])
            ->get()
            ->map(function ($campaign) {
                $totalSpend = $campaign->metrics->sum('spend');
                $totalRevenue = $campaign->metrics->sum('revenue');
                $roi = $totalSpend > 0 ? (($totalRevenue - $totalSpend) / $totalSpend) * 100 : 0;

                return [
                    'id' => $campaign->id,
                    'name' => $campaign->campaign_name,
                    'type' => $campaign->campaign_type,
                    'conversions' => $campaign->metrics->sum('conversions'),
                    'spend' => $totalSpend,
                    'revenue' => $totalRevenue,
                    'roi' => round($roi, 1),
                ];
            })
            ->sortByDesc('roi')
            ->take(5)
            ->values()
            ->toArray();
    }

    public function getStatusBadgeClass(string $status): string
    {
        return match ($status) {
            'active' => 'bg-green-100 text-green-800',
            'paused' => 'bg-yellow-100 text-yellow-800',
            'planning' => 'bg-blue-100 text-blue-800',
            'completed' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-600',
        };
    }

    public function getTypeBadgeClass(string $type): string
    {
        return match ($type) {
            'social' => 'bg-pink-100 text-pink-800',
            'email' => 'bg-purple-100 text-purple-800',
            'ppc' => 'bg-orange-100 text-orange-800',
            'content' => 'bg-teal-100 text-teal-800',
            'seo' => 'bg-indigo-100 text-indigo-800',
            'launch' => 'bg-red-100 text-red-800',
            'event' => 'bg-cyan-100 text-cyan-800',
            'seasonal' => 'bg-amber-100 text-amber-800',
            default => 'bg-gray-100 text-gray-600',
        };
    }

    public function render()
    {
        return view('livewire.client.campaigns-dashboard', [
            'campaigns' => $this->campaigns,
            'overviewStats' => $this->overviewStats,
            'statusCounts' => $this->statusCounts,
            'typeCounts' => $this->typeCounts,
            'chartData' => $this->chartData,
            'topCampaigns' => $this->topCampaigns,
        ])->layout('layouts.app', ['title' => 'Campaigns Dashboard']);
    }
}
