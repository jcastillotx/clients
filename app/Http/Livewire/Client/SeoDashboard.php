<?php

namespace App\Http\Livewire\Client;

use App\Models\Client;
use App\Models\KeywordRanking;
use App\Models\SeoKeyword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Livewire\WithPagination;

class SeoDashboard extends Component
{
    use WithPagination;

    public string $activeTab = 'overview';
    public string $websiteUrl = '';
    public array $pageSpeedData = [];
    public array $securityData = [];
    public array $searchEngineRankings = [];
    public bool $isLoadingPageSpeed = false;
    public bool $isLoadingSecurity = false;
    public string $selectedDevice = 'mobile';

    protected $queryString = ['activeTab'];

    public function mount(): void
    {
        abort_unless(Auth::user()?->isClient(), 403);

        $client = Auth::user()->client;
        if ($client && $client->website) {
            $this->websiteUrl = $client->website;
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function runPageSpeedAnalysis(): void
    {
        if (empty($this->websiteUrl)) {
            $this->addError('websiteUrl', 'Please enter a website URL');
            return;
        }

        $this->isLoadingPageSpeed = true;

        $cacheKey = 'pagespeed_' . md5($this->websiteUrl . $this->selectedDevice);

        $this->pageSpeedData = Cache::remember($cacheKey, now()->addHours(24), function () {
            return $this->fetchPageSpeedData();
        });

        $this->isLoadingPageSpeed = false;
    }

    protected function fetchPageSpeedData(): array
    {
        $apiKey = config('seo.free.google_pagespeed.api_key');
        $endpoint = config('seo.free.google_pagespeed.endpoint');

        if (empty($apiKey) || !config('seo.free.google_pagespeed.enabled')) {
            return $this->getMockPageSpeedData();
        }

        try {
            $response = Http::get("{$endpoint}/runPagespeed", [
                'url' => $this->websiteUrl,
                'key' => $apiKey,
                'strategy' => $this->selectedDevice,
                'category' => ['performance', 'accessibility', 'best-practices', 'seo'],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $lighthouse = $data['lighthouseResult'] ?? [];
                $categories = $lighthouse['categories'] ?? [];

                return [
                    'performance' => (int) (($categories['performance']['score'] ?? 0) * 100),
                    'accessibility' => (int) (($categories['accessibility']['score'] ?? 0) * 100),
                    'best_practices' => (int) (($categories['best-practices']['score'] ?? 0) * 100),
                    'seo' => (int) (($categories['seo']['score'] ?? 0) * 100),
                    'fcp' => $lighthouse['audits']['first-contentful-paint']['displayValue'] ?? 'N/A',
                    'lcp' => $lighthouse['audits']['largest-contentful-paint']['displayValue'] ?? 'N/A',
                    'cls' => $lighthouse['audits']['cumulative-layout-shift']['displayValue'] ?? 'N/A',
                    'tbt' => $lighthouse['audits']['total-blocking-time']['displayValue'] ?? 'N/A',
                    'speed_index' => $lighthouse['audits']['speed-index']['displayValue'] ?? 'N/A',
                    'tti' => $lighthouse['audits']['interactive']['displayValue'] ?? 'N/A',
                    'device' => $this->selectedDevice,
                    'fetched_at' => now()->toDateTimeString(),
                ];
            }

            return $this->getMockPageSpeedData();
        } catch (\Exception $e) {
            return $this->getMockPageSpeedData();
        }
    }

    protected function getMockPageSpeedData(): array
    {
        return [
            'performance' => rand(60, 95),
            'accessibility' => rand(80, 100),
            'best_practices' => rand(70, 95),
            'seo' => rand(85, 100),
            'fcp' => rand(10, 30) / 10 . ' s',
            'lcp' => rand(15, 40) / 10 . ' s',
            'cls' => '0.' . rand(1, 25),
            'tbt' => rand(100, 500) . ' ms',
            'speed_index' => rand(20, 50) / 10 . ' s',
            'tti' => rand(25, 60) / 10 . ' s',
            'device' => $this->selectedDevice,
            'fetched_at' => now()->toDateTimeString(),
            'is_mock' => true,
        ];
    }

    public function runSecurityScan(): void
    {
        if (empty($this->websiteUrl)) {
            $this->addError('websiteUrl', 'Please enter a website URL');
            return;
        }

        $this->isLoadingSecurity = true;

        $cacheKey = 'security_' . md5($this->websiteUrl);

        $this->securityData = Cache::remember($cacheKey, now()->addHours(24), function () {
            return $this->fetchSecurityData();
        });

        $this->isLoadingSecurity = false;
    }

    protected function fetchSecurityData(): array
    {
        $url = $this->websiteUrl;
        $parsedUrl = parse_url($url);

        $checks = [
            'ssl_enabled' => str_starts_with($url, 'https://'),
            'hsts_enabled' => false,
            'xss_protection' => false,
            'content_type_options' => false,
            'frame_options' => false,
            'content_security_policy' => false,
        ];

        try {
            $response = Http::timeout(10)->get($url);
            $headers = $response->headers();

            $checks['hsts_enabled'] = isset($headers['Strict-Transport-Security']);
            $checks['xss_protection'] = isset($headers['X-XSS-Protection']);
            $checks['content_type_options'] = isset($headers['X-Content-Type-Options']);
            $checks['frame_options'] = isset($headers['X-Frame-Options']);
            $checks['content_security_policy'] = isset($headers['Content-Security-Policy']);
        } catch (\Exception $e) {
            // Continue with default checks
        }

        $score = array_sum(array_map(fn($v) => $v ? 1 : 0, $checks));
        $totalChecks = count($checks);

        return [
            'score' => (int) (($score / $totalChecks) * 100),
            'checks' => $checks,
            'recommendations' => $this->getSecurityRecommendations($checks),
            'fetched_at' => now()->toDateTimeString(),
        ];
    }

    protected function getSecurityRecommendations(array $checks): array
    {
        $recommendations = [];

        if (!$checks['ssl_enabled']) {
            $recommendations[] = [
                'severity' => 'critical',
                'title' => 'Enable HTTPS',
                'description' => 'Your website is not using HTTPS. This is critical for security and SEO rankings.',
            ];
        }

        if (!$checks['hsts_enabled']) {
            $recommendations[] = [
                'severity' => 'high',
                'title' => 'Enable HSTS',
                'description' => 'HTTP Strict Transport Security (HSTS) forces browsers to use HTTPS.',
            ];
        }

        if (!$checks['content_security_policy']) {
            $recommendations[] = [
                'severity' => 'medium',
                'title' => 'Add Content Security Policy',
                'description' => 'CSP helps prevent XSS attacks by controlling resource loading.',
            ];
        }

        if (!$checks['xss_protection']) {
            $recommendations[] = [
                'severity' => 'medium',
                'title' => 'Enable XSS Protection Header',
                'description' => 'Add X-XSS-Protection header to help prevent cross-site scripting attacks.',
            ];
        }

        if (!$checks['frame_options']) {
            $recommendations[] = [
                'severity' => 'medium',
                'title' => 'Add X-Frame-Options',
                'description' => 'Prevent clickjacking attacks by controlling iframe embedding.',
            ];
        }

        if (!$checks['content_type_options']) {
            $recommendations[] = [
                'severity' => 'low',
                'title' => 'Add X-Content-Type-Options',
                'description' => 'Prevent MIME type sniffing with nosniff directive.',
            ];
        }

        return $recommendations;
    }

    public function getKeywordsProperty()
    {
        $clientId = Auth::user()->client_id;

        return SeoKeyword::where('client_id', $clientId)
            ->where('tracking_enabled', true)
            ->orderBy('current_position')
            ->paginate(10);
    }

    public function getKeywordStatsProperty(): array
    {
        $clientId = Auth::user()->client_id;

        $keywords = SeoKeyword::where('client_id', $clientId)
            ->where('tracking_enabled', true)
            ->get();

        $totalKeywords = $keywords->count();
        $avgPosition = $keywords->avg('current_position') ?? 0;
        $top10 = $keywords->where('current_position', '<=', 10)->count();
        $top3 = $keywords->where('current_position', '<=', 3)->count();

        return [
            'total' => $totalKeywords,
            'avg_position' => round($avgPosition, 1),
            'top_10' => $top10,
            'top_3' => $top3,
            'top_10_percentage' => $totalKeywords > 0 ? round(($top10 / $totalKeywords) * 100, 1) : 0,
        ];
    }

    public function getSearchEngineDataProperty(): array
    {
        $clientId = Auth::user()->client_id;

        $engines = ['google', 'bing', 'yahoo', 'duckduckgo'];
        $data = [];

        foreach ($engines as $engine) {
            $rankings = KeywordRanking::whereHas('keyword', function ($q) use ($clientId) {
                $q->where('client_id', $clientId);
            })
                ->where('search_engine', $engine)
                ->latest('tracked_at')
                ->take(100)
                ->get();

            $data[$engine] = [
                'name' => ucfirst($engine),
                'avg_position' => $rankings->avg('position') ?? 0,
                'keywords_tracked' => $rankings->count(),
                'top_10' => $rankings->where('position', '<=', 10)->count(),
                'icon' => $this->getSearchEngineIcon($engine),
            ];
        }

        return $data;
    }

    protected function getSearchEngineIcon(string $engine): string
    {
        return match ($engine) {
            'google' => 'fab fa-google',
            'bing' => 'fab fa-microsoft',
            'yahoo' => 'fab fa-yahoo',
            'duckduckgo' => 'fas fa-duck',
            default => 'fas fa-search',
        };
    }

    public function getOverviewStatsProperty(): array
    {
        $keywordStats = $this->keywordStats;

        return [
            'overall_score' => $this->calculateOverallScore(),
            'keywords_tracked' => $keywordStats['total'],
            'avg_position' => $keywordStats['avg_position'],
            'top_10_keywords' => $keywordStats['top_10'],
        ];
    }

    protected function calculateOverallScore(): int
    {
        $scores = [];

        if (!empty($this->pageSpeedData)) {
            $scores[] = $this->pageSpeedData['seo'] ?? 0;
        }

        if (!empty($this->securityData)) {
            $scores[] = $this->securityData['score'] ?? 0;
        }

        $keywordScore = $this->keywordStats['top_10_percentage'] ?? 0;
        if ($keywordScore > 0) {
            $scores[] = min($keywordScore, 100);
        }

        return count($scores) > 0 ? (int) (array_sum($scores) / count($scores)) : 0;
    }

    public function render()
    {
        return view('livewire.client.seo-dashboard', [
            'keywords' => $this->keywords,
            'keywordStats' => $this->keywordStats,
            'searchEngineData' => $this->searchEngineData,
            'overviewStats' => $this->overviewStats,
        ])->layout('layouts.app', ['title' => 'SEO Dashboard']);
    }
}
