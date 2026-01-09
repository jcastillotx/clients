<?php

namespace App\Http\Livewire\Client;

use App\Models\Client;
use App\Models\KeywordRanking;
use App\Models\SeoKeyword;
use App\Services\SEO\GooglePageSpeedService;
use App\Services\SEO\GoogleSearchConsoleService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class SeoDashboard extends Component
{
    use WithPagination;

    public string $activeTab = 'overview';
    public string $websiteUrl = '';
    public array $pageSpeedData = [];
    public array $pageSpeedDesktop = [];
    public array $securityData = [];
    public bool $isLoading = false;
    public string $dateRange = '28';
    public bool $gscConnected = false;

    // Chart data
    public array $dailyTrendData = [];
    public array $deviceData = [];
    public array $countryData = [];
    public array $topPagesData = [];

    protected $queryString = ['activeTab'];

    protected $listeners = ['refreshData' => '$refresh'];

    public function mount(): void
    {
        abort_unless(Auth::user()?->isClient(), 403);

        $client = Auth::user()->client;
        if ($client) {
            $this->websiteUrl = $client->website ?? '';
            $this->gscConnected = !empty($client->gsc_refresh_token);

            // Load initial data if GSC is connected
            if ($this->gscConnected && $this->websiteUrl) {
                $this->loadGscData();
            }
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updatedDateRange(): void
    {
        $this->loadGscData();
    }

    public function loadGscData(): void
    {
        if (!$this->gscConnected || empty($this->websiteUrl)) {
            return;
        }

        $client = Auth::user()->client;
        $gsc = app(GoogleSearchConsoleService::class);

        // Load daily trend for charts
        $trend = $gsc->getDailyTrend($client, $this->websiteUrl, (int) $this->dateRange);
        if ($trend['success'] ?? false) {
            $this->dailyTrendData = $trend['data'];
        }

        // Load device breakdown
        $devices = $gsc->getPerformanceByDevice($client, $this->websiteUrl, (int) $this->dateRange);
        if ($devices['success'] ?? false) {
            $this->deviceData = $devices['data'];
        }

        // Load country data
        $countries = $gsc->getPerformanceByCountry($client, $this->websiteUrl, (int) $this->dateRange);
        if ($countries['success'] ?? false) {
            $this->countryData = array_slice($countries['data'], 0, 10);
        }

        // Load top pages
        $pages = $gsc->getTopPages($client, $this->websiteUrl, (int) $this->dateRange, 10);
        if ($pages['success'] ?? false) {
            $this->topPagesData = $pages['data'];
        }
    }

    public function syncFromGsc(): void
    {
        if (!$this->gscConnected || empty($this->websiteUrl)) {
            session()->flash('error', 'Google Search Console is not connected.');
            return;
        }

        $this->isLoading = true;

        $client = Auth::user()->client;
        $gsc = app(GoogleSearchConsoleService::class);

        $result = $gsc->syncKeywordData($client, $this->websiteUrl);

        if ($result['success']) {
            session()->flash('success', "Synced {$result['total']} keywords from Google Search Console.");
            $this->loadGscData();
        } else {
            session()->flash('error', $result['message'] ?? 'Failed to sync data.');
        }

        $this->isLoading = false;
    }

    public function runPageSpeedAnalysis(): void
    {
        if (empty($this->websiteUrl)) {
            session()->flash('error', 'Please enter a website URL');
            return;
        }

        $this->isLoading = true;

        $pageSpeed = app(GooglePageSpeedService::class);

        // Run both mobile and desktop
        $mobile = $pageSpeed->analyze($this->websiteUrl, 'mobile', ['performance', 'accessibility', 'best-practices', 'seo']);
        $desktop = $pageSpeed->analyze($this->websiteUrl, 'desktop', ['performance', 'accessibility', 'best-practices', 'seo']);

        if ($mobile['success'] ?? false) {
            $this->pageSpeedData = [
                'scores' => $mobile['scores'],
                'core_web_vitals' => $mobile['core_web_vitals'],
                'opportunities' => $mobile['opportunities'] ?? [],
                'fetched_at' => now()->toDateTimeString(),
            ];
        }

        if ($desktop['success'] ?? false) {
            $this->pageSpeedDesktop = [
                'scores' => $desktop['scores'],
                'core_web_vitals' => $desktop['core_web_vitals'],
                'opportunities' => $desktop['opportunities'] ?? [],
                'fetched_at' => now()->toDateTimeString(),
            ];
        }

        $this->isLoading = false;
    }

    public function runSecurityScan(): void
    {
        if (empty($this->websiteUrl)) {
            session()->flash('error', 'Please enter a website URL');
            return;
        }

        $this->isLoading = true;
        $this->securityData = $this->fetchSecurityData();
        $this->isLoading = false;
    }

    protected function fetchSecurityData(): array
    {
        $url = $this->websiteUrl;

        $checks = [
            'ssl_enabled' => str_starts_with($url, 'https://'),
            'hsts_enabled' => false,
            'xss_protection' => false,
            'content_type_options' => false,
            'frame_options' => false,
            'content_security_policy' => false,
            'referrer_policy' => false,
            'permissions_policy' => false,
        ];

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)->get($url);
            $headers = $response->headers();

            $checks['hsts_enabled'] = isset($headers['Strict-Transport-Security']);
            $checks['xss_protection'] = isset($headers['X-XSS-Protection']);
            $checks['content_type_options'] = isset($headers['X-Content-Type-Options']);
            $checks['frame_options'] = isset($headers['X-Frame-Options']);
            $checks['content_security_policy'] = isset($headers['Content-Security-Policy']);
            $checks['referrer_policy'] = isset($headers['Referrer-Policy']);
            $checks['permissions_policy'] = isset($headers['Permissions-Policy']);
        } catch (\Exception $e) {
            // Continue with default checks
        }

        $passed = array_sum(array_map(fn($v) => $v ? 1 : 0, $checks));
        $total = count($checks);

        return [
            'score' => (int) (($passed / $total) * 100),
            'passed' => $passed,
            'total' => $total,
            'checks' => $checks,
            'recommendations' => $this->getSecurityRecommendations($checks),
            'fetched_at' => now()->toDateTimeString(),
        ];
    }

    protected function getSecurityRecommendations(array $checks): array
    {
        $recommendations = [];

        $items = [
            'ssl_enabled' => ['critical', 'Enable HTTPS', 'Your website is not using HTTPS. Critical for security and SEO.'],
            'hsts_enabled' => ['high', 'Enable HSTS', 'HTTP Strict Transport Security forces browsers to use HTTPS.'],
            'content_security_policy' => ['high', 'Add Content Security Policy', 'CSP helps prevent XSS attacks.'],
            'xss_protection' => ['medium', 'Enable XSS Protection', 'Add X-XSS-Protection header.'],
            'frame_options' => ['medium', 'Add X-Frame-Options', 'Prevent clickjacking attacks.'],
            'content_type_options' => ['low', 'Add X-Content-Type-Options', 'Prevent MIME type sniffing.'],
            'referrer_policy' => ['low', 'Add Referrer-Policy', 'Control referrer information.'],
            'permissions_policy' => ['low', 'Add Permissions-Policy', 'Control browser features.'],
        ];

        foreach ($items as $check => $info) {
            if (!$checks[$check]) {
                $recommendations[] = [
                    'severity' => $info[0],
                    'title' => $info[1],
                    'description' => $info[2],
                ];
            }
        }

        return $recommendations;
    }

    public function getKeywordsProperty()
    {
        $clientId = Auth::user()->client_id;

        return SeoKeyword::where('client_id', $clientId)
            ->where('tracking_enabled', true)
            ->orderBy('current_position')
            ->paginate(15);
    }

    public function getKeywordStatsProperty(): array
    {
        $clientId = Auth::user()->client_id;

        $keywords = SeoKeyword::where('client_id', $clientId)
            ->where('tracking_enabled', true)
            ->get();

        $totalKeywords = $keywords->count();
        $avgPosition = $keywords->whereNotNull('current_position')->avg('current_position') ?? 0;
        $top3 = $keywords->where('current_position', '<=', 3)->where('current_position', '>', 0)->count();
        $top10 = $keywords->where('current_position', '<=', 10)->where('current_position', '>', 0)->count();
        $top20 = $keywords->where('current_position', '<=', 20)->where('current_position', '>', 0)->count();

        $totalClicks = $keywords->sum(fn($k) => $k->meta['clicks'] ?? 0);
        $totalImpressions = $keywords->sum(fn($k) => $k->meta['impressions'] ?? 0);

        return [
            'total' => $totalKeywords,
            'avg_position' => round($avgPosition, 1),
            'top_3' => $top3,
            'top_10' => $top10,
            'top_20' => $top20,
            'total_clicks' => $totalClicks,
            'total_impressions' => $totalImpressions,
            'avg_ctr' => $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0,
        ];
    }

    public function getPositionDistributionProperty(): array
    {
        $clientId = Auth::user()->client_id;

        $keywords = SeoKeyword::where('client_id', $clientId)
            ->where('tracking_enabled', true)
            ->whereNotNull('current_position')
            ->get();

        return [
            '1-3' => $keywords->whereBetween('current_position', [1, 3])->count(),
            '4-10' => $keywords->whereBetween('current_position', [4, 10])->count(),
            '11-20' => $keywords->whereBetween('current_position', [11, 20])->count(),
            '21-50' => $keywords->whereBetween('current_position', [21, 50])->count(),
            '50+' => $keywords->where('current_position', '>', 50)->count(),
        ];
    }

    public function getRankingTrendProperty(): array
    {
        $clientId = Auth::user()->client_id;

        $keywordIds = SeoKeyword::where('client_id', $clientId)->pluck('id');

        return KeywordRanking::whereIn('seo_keyword_id', $keywordIds)
            ->where('search_engine', 'google')
            ->where('tracked_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(tracked_at) as date, AVG(position) as avg_position, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($r) => [
                'date' => $r->date,
                'position' => round($r->avg_position, 1),
                'count' => $r->count,
            ])
            ->toArray();
    }

    public function getOverallScoreProperty(): int
    {
        $scores = [];

        // PageSpeed SEO score
        if (!empty($this->pageSpeedData['scores']['seo'])) {
            $scores[] = $this->pageSpeedData['scores']['seo'];
        }

        // Security score
        if (!empty($this->securityData['score'])) {
            $scores[] = $this->securityData['score'];
        }

        // Keyword ranking score (based on % in top 10)
        $stats = $this->keywordStats;
        if ($stats['total'] > 0) {
            $scores[] = min(100, (int) (($stats['top_10'] / $stats['total']) * 100));
        }

        return count($scores) > 0 ? (int) (array_sum($scores) / count($scores)) : 0;
    }

    public function render()
    {
        return view('livewire.client.seo-dashboard', [
            'keywords' => $this->keywords,
            'keywordStats' => $this->keywordStats,
            'positionDistribution' => $this->positionDistribution,
            'rankingTrend' => $this->rankingTrend,
            'overallScore' => $this->overallScore,
        ])->layout('layouts.app', ['title' => 'SEO Dashboard']);
    }
}
