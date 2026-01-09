<?php

namespace App\Http\Livewire\Client;

use App\Models\Client;
use App\Models\KeywordRanking;
use App\Models\LocalRanking;
use App\Models\SeoKeyword;
use App\Services\SEO\GooglePageSpeedService;
use App\Services\SEO\GoogleSearchConsoleService;
use App\Services\SEO\LocalSeoService;
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

    // Local SEO data
    public string $localKeyword = '';
    public string $localLocation = '';
    public string $businessName = '';
    public float $businessLat = 0;
    public float $businessLng = 0;
    public int $gridSize = 5;
    public float $gridRadius = 5;
    public array $mapPackResults = [];
    public array $gridRankingData = [];
    public array $localCompetitors = [];
    public bool $localSeoConfigured = false;

    protected $queryString = ['activeTab'];

    protected $listeners = ['refreshData' => '$refresh', 'setBusinessLocation'];

    public function mount(): void
    {
        abort_unless(Auth::user()?->isClient(), 403);

        $client = Auth::user()->client;
        if ($client) {
            $this->websiteUrl = $client->website ?? '';
            $this->gscConnected = !empty($client->gsc_refresh_token);
            $this->businessName = $client->company_name ?? '';

            // Load Local SEO settings from client meta
            $localSeoSettings = $client->meta['local_seo'] ?? [];
            $this->businessLat = (float) ($localSeoSettings['lat'] ?? 0);
            $this->businessLng = (float) ($localSeoSettings['lng'] ?? 0);
            $this->localLocation = $localSeoSettings['location'] ?? '';

            // Check if Local SEO service is configured
            $this->localSeoConfigured = app(LocalSeoService::class)->isConfigured();

            // Load initial data if GSC is connected
            if ($this->gscConnected && $this->websiteUrl) {
                $this->loadGscData();
            }

            // Load latest local ranking if available
            $this->loadLatestLocalRanking();
        }
    }

    public function setBusinessLocation(float $lat, float $lng): void
    {
        $this->businessLat = $lat;
        $this->businessLng = $lng;

        // Save to client meta
        $client = Auth::user()->client;
        $meta = $client->meta ?? [];
        $meta['local_seo'] = array_merge($meta['local_seo'] ?? [], [
            'lat' => $lat,
            'lng' => $lng,
            'location' => $this->localLocation,
        ]);
        $client->update(['meta' => $meta]);
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

    // ==================== LOCAL SEO METHODS ====================

    public function loadLatestLocalRanking(): void
    {
        $clientId = Auth::user()->client_id;

        $latest = LocalRanking::where('client_id', $clientId)
            ->orderBy('tracked_at', 'desc')
            ->first();

        if ($latest) {
            $this->gridRankingData = [
                'keyword' => $latest->keyword,
                'grid_results' => $latest->grid_data ?? [],
                'stats' => [
                    'average_position' => $latest->average_position,
                    'top_3_count' => $latest->top_3_count,
                    'visibility_score' => $latest->visibility_score,
                ],
                'grid_size' => $latest->grid_size,
                'tracked_at' => $latest->tracked_at?->format('M d, Y g:i A'),
            ];
            $this->localKeyword = $latest->keyword;
            $this->businessLat = (float) $latest->center_lat;
            $this->businessLng = (float) $latest->center_lng;
            $this->gridSize = $latest->grid_size ?? 5;
            $this->gridRadius = (float) ($latest->radius_miles ?? 5);
        }
    }

    public function searchMapPack(): void
    {
        if (empty($this->localKeyword) || empty($this->localLocation)) {
            session()->flash('error', 'Please enter a keyword and location.');
            return;
        }

        $this->isLoading = true;

        $localSeo = app(LocalSeoService::class);
        $result = $localSeo->getMapPackResults($this->localKeyword, $this->localLocation);

        if ($result['success']) {
            $this->mapPackResults = $result['results'];

            // Find competitors
            $competitors = $localSeo->findLocalCompetitors(
                $this->localKeyword,
                $this->localLocation,
                $this->businessName
            );

            if ($competitors['success']) {
                $this->localCompetitors = $competitors['competitors'];
            }

            session()->flash('success', 'Found ' . count($this->mapPackResults) . ' local results.');
        } else {
            session()->flash('error', $result['error'] ?? 'Failed to fetch map pack results.');
        }

        $this->isLoading = false;
    }

    public function runGridAnalysis(): void
    {
        if (empty($this->localKeyword)) {
            session()->flash('error', 'Please enter a keyword.');
            return;
        }

        if ($this->businessLat == 0 || $this->businessLng == 0) {
            session()->flash('error', 'Please set your business location on the map.');
            return;
        }

        $this->isLoading = true;

        $localSeo = app(LocalSeoService::class);
        $result = $localSeo->getGridRankings(
            $this->localKeyword,
            $this->businessLat,
            $this->businessLng,
            $this->gridSize,
            $this->gridRadius,
            $this->businessName
        );

        if ($result['success']) {
            $this->gridRankingData = $result;

            // Save to database
            $localSeo->saveGridRanking(Auth::user()->client, $this->localKeyword, $result);

            session()->flash('success', 'Grid analysis complete. Visibility score: ' . $result['stats']['visibility_score'] . '%');
        } else {
            session()->flash('error', $result['error'] ?? 'Failed to run grid analysis.');
        }

        $this->isLoading = false;
    }

    public function getLocalRankingHistoryProperty(): array
    {
        $clientId = Auth::user()->client_id;

        return LocalRanking::where('client_id', $clientId)
            ->orderBy('tracked_date', 'desc')
            ->limit(30)
            ->get()
            ->map(fn($r) => [
                'date' => $r->tracked_date->format('M d'),
                'keyword' => $r->keyword,
                'visibility_score' => $r->visibility_score,
                'average_position' => $r->average_position,
                'top_3_count' => $r->top_3_count,
            ])
            ->toArray();
    }

    public function getLocalKeywordsProperty(): array
    {
        $clientId = Auth::user()->client_id;

        return LocalRanking::where('client_id', $clientId)
            ->select('keyword')
            ->distinct()
            ->orderBy('keyword')
            ->pluck('keyword')
            ->toArray();
    }

    public static function getGridPositionColor(?int $position): string
    {
        if ($position === null) {
            return '#e5e7eb'; // gray-200
        }

        return match (true) {
            $position === 1 => '#22c55e', // green-500
            $position === 2 => '#4ade80', // green-400
            $position === 3 => '#86efac', // green-300
            $position <= 5 => '#fde047', // yellow-300
            $position <= 10 => '#fdba74', // orange-300
            $position <= 20 => '#fca5a5', // red-300
            default => '#ef4444', // red-500
        };
    }

    public function render()
    {
        return view('livewire.client.seo-dashboard', [
            'keywords' => $this->keywords,
            'keywordStats' => $this->keywordStats,
            'positionDistribution' => $this->positionDistribution,
            'rankingTrend' => $this->rankingTrend,
            'overallScore' => $this->overallScore,
            'localRankingHistory' => $this->localRankingHistory,
            'localKeywords' => $this->localKeywords,
        ])->layout('layouts.app', ['title' => 'SEO Dashboard']);
    }
}
