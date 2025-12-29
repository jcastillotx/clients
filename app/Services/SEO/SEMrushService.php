<?php

namespace App\Services\SEO;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SEMrush API Service
 *
 * Premium SEO data provider (from $129.95/month).
 * Features: Domain Analytics, Keyword Analytics, Backlinks, Position Tracking
 *
 * @see https://www.semrush.com/api-documentation/
 */
class SEMrushService
{
    protected string $endpoint = 'https://api.semrush.com';

    protected ?string $apiKey;

    protected bool $enabled;

    public function __construct()
    {
        $this->apiKey = config('seo.commercial.semrush.api_key') ?: app('settings')->get('api.seo.semrush.api_key');
        $this->enabled = (bool) (config('seo.commercial.semrush.enabled') ?? app('settings')->get('api.seo.semrush.enabled', false));
    }

    public function isConfigured(): bool
    {
        return $this->enabled && ! empty($this->apiKey);
    }

    /**
     * Get domain overview metrics
     *
     * @return array<string, mixed>
     */
    public function getDomainOverview(string $domain, string $database = 'us'): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'SEMrush not configured', 'data' => []];
        }

        $cacheKey = 'semrush:domain_overview:'.md5($domain.$database);

        return Cache::remember($cacheKey, config('seo.cache.domain_metrics_ttl', 86400), function () use ($domain, $database) {
            try {
                $response = Http::timeout(30)
                    ->get($this->endpoint, [
                        'type' => 'domain_ranks',
                        'key' => $this->apiKey,
                        'domain' => $domain,
                        'database' => $database,
                        'export_columns' => 'Dn,Rk,Or,Ot,Oc,Ad,At,Ac,Sh',
                    ]);

                if ($response->successful() && ! str_contains($response->body(), 'ERROR')) {
                    $lines = explode("\n", trim($response->body()));
                    if (count($lines) >= 2) {
                        $headers = str_getcsv($lines[0], ';');
                        $values = str_getcsv($lines[1], ';');
                        $data = array_combine($headers, $values);

                        return [
                            'success' => true,
                            'domain' => $domain,
                            'semrush_rank' => (int) ($data['Rk'] ?? 0),
                            'organic_keywords' => (int) ($data['Or'] ?? 0),
                            'organic_traffic' => (int) ($data['Ot'] ?? 0),
                            'organic_cost' => (float) ($data['Oc'] ?? 0),
                            'adwords_keywords' => (int) ($data['Ad'] ?? 0),
                            'adwords_traffic' => (int) ($data['At'] ?? 0),
                            'adwords_cost' => (float) ($data['Ac'] ?? 0),
                        ];
                    }
                }

                return ['error' => 'Invalid response: '.substr($response->body(), 0, 100), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('SEMrush domain overview error', ['error' => $e->getMessage()]);

                return ['error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Get organic keywords for a domain
     *
     * @return array<string, mixed>
     */
    public function getOrganicKeywords(string $domain, string $database = 'us', int $limit = 100): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'SEMrush not configured', 'data' => []];
        }

        $cacheKey = 'semrush:organic_keywords:'.md5($domain.$database.$limit);

        return Cache::remember($cacheKey, config('seo.cache.keyword_data_ttl', 86400), function () use ($domain, $database, $limit) {
            try {
                $response = Http::timeout(60)
                    ->get($this->endpoint, [
                        'type' => 'domain_organic',
                        'key' => $this->apiKey,
                        'domain' => $domain,
                        'database' => $database,
                        'display_limit' => $limit,
                        'export_columns' => 'Ph,Po,Nq,Cp,Co,Tr,Tc,Ur',
                    ]);

                if ($response->successful() && ! str_contains($response->body(), 'ERROR')) {
                    $lines = explode("\n", trim($response->body()));
                    $headers = str_getcsv(array_shift($lines), ';');
                    $keywords = [];

                    foreach ($lines as $line) {
                        if (empty(trim($line))) {
                            continue;
                        }
                        $values = str_getcsv($line, ';');
                        if (count($values) === count($headers)) {
                            $row = array_combine($headers, $values);
                            $keywords[] = [
                                'keyword' => $row['Ph'] ?? '',
                                'position' => (int) ($row['Po'] ?? 0),
                                'volume' => (int) ($row['Nq'] ?? 0),
                                'cpc' => (float) ($row['Cp'] ?? 0),
                                'competition' => (float) ($row['Co'] ?? 0),
                                'traffic' => (float) ($row['Tr'] ?? 0),
                                'traffic_cost' => (float) ($row['Tc'] ?? 0),
                                'url' => $row['Ur'] ?? '',
                            ];
                        }
                    }

                    return [
                        'success' => true,
                        'domain' => $domain,
                        'keywords' => $keywords,
                    ];
                }

                return ['error' => 'Invalid response: '.substr($response->body(), 0, 100), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('SEMrush organic keywords error', ['error' => $e->getMessage()]);

                return ['error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Get keyword overview
     *
     * @param  array<string>  $keywords
     * @return array<string, mixed>
     */
    public function getKeywordOverview(array $keywords, string $database = 'us'): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'SEMrush not configured', 'data' => []];
        }

        $keyword = implode(';', array_slice($keywords, 0, 100)); // API limit
        $cacheKey = 'semrush:keyword_overview:'.md5($keyword.$database);

        return Cache::remember($cacheKey, config('seo.cache.keyword_data_ttl', 86400), function () use ($keyword, $database) {
            try {
                $response = Http::timeout(30)
                    ->get($this->endpoint, [
                        'type' => 'phrase_all',
                        'key' => $this->apiKey,
                        'phrase' => $keyword,
                        'database' => $database,
                        'export_columns' => 'Ph,Nq,Cp,Co,Nr,Td',
                    ]);

                if ($response->successful() && ! str_contains($response->body(), 'ERROR')) {
                    $lines = explode("\n", trim($response->body()));
                    $headers = str_getcsv(array_shift($lines), ';');
                    $results = [];

                    foreach ($lines as $line) {
                        if (empty(trim($line))) {
                            continue;
                        }
                        $values = str_getcsv($line, ';');
                        if (count($values) === count($headers)) {
                            $row = array_combine($headers, $values);
                            $results[] = [
                                'keyword' => $row['Ph'] ?? '',
                                'volume' => (int) ($row['Nq'] ?? 0),
                                'cpc' => (float) ($row['Cp'] ?? 0),
                                'competition' => (float) ($row['Co'] ?? 0),
                                'results' => (int) ($row['Nr'] ?? 0),
                                'trend' => $row['Td'] ?? null,
                            ];
                        }
                    }

                    return [
                        'success' => true,
                        'keywords' => $results,
                    ];
                }

                return ['error' => 'Invalid response: '.substr($response->body(), 0, 100), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('SEMrush keyword overview error', ['error' => $e->getMessage()]);

                return ['error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Get backlinks overview
     *
     * @return array<string, mixed>
     */
    public function getBacklinksOverview(string $target): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'SEMrush not configured', 'data' => []];
        }

        $cacheKey = 'semrush:backlinks_overview:'.md5($target);

        return Cache::remember($cacheKey, config('seo.cache.backlink_data_ttl', 604800), function () use ($target) {
            try {
                $response = Http::timeout(30)
                    ->get($this->endpoint, [
                        'type' => 'backlinks_overview',
                        'key' => $this->apiKey,
                        'target' => $target,
                        'target_type' => 'root_domain',
                        'export_columns' => 'total,domains_num,urls_num,ips_num,follows_num,nofollows_num,texts_num,images_num,forms_num,frames_num',
                    ]);

                if ($response->successful() && ! str_contains($response->body(), 'ERROR')) {
                    $lines = explode("\n", trim($response->body()));
                    if (count($lines) >= 2) {
                        $headers = str_getcsv($lines[0], ';');
                        $values = str_getcsv($lines[1], ';');
                        $data = array_combine($headers, $values);

                        return [
                            'success' => true,
                            'target' => $target,
                            'total_backlinks' => (int) ($data['total'] ?? 0),
                            'referring_domains' => (int) ($data['domains_num'] ?? 0),
                            'referring_urls' => (int) ($data['urls_num'] ?? 0),
                            'referring_ips' => (int) ($data['ips_num'] ?? 0),
                            'dofollow' => (int) ($data['follows_num'] ?? 0),
                            'nofollow' => (int) ($data['nofollows_num'] ?? 0),
                            'text_links' => (int) ($data['texts_num'] ?? 0),
                            'image_links' => (int) ($data['images_num'] ?? 0),
                        ];
                    }
                }

                return ['error' => 'Invalid response: '.substr($response->body(), 0, 100), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('SEMrush backlinks overview error', ['error' => $e->getMessage()]);

                return ['error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Get competitor domains (organic)
     *
     * @return array<string, mixed>
     */
    public function getOrganicCompetitors(string $domain, string $database = 'us', int $limit = 20): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'SEMrush not configured', 'data' => []];
        }

        $cacheKey = 'semrush:organic_competitors:'.md5($domain.$database.$limit);

        return Cache::remember($cacheKey, config('seo.cache.keyword_data_ttl', 86400), function () use ($domain, $database, $limit) {
            try {
                $response = Http::timeout(30)
                    ->get($this->endpoint, [
                        'type' => 'domain_organic_organic',
                        'key' => $this->apiKey,
                        'domain' => $domain,
                        'database' => $database,
                        'display_limit' => $limit,
                        'export_columns' => 'Dn,Cr,Np,Or,Ot,Oc,Ad',
                    ]);

                if ($response->successful() && ! str_contains($response->body(), 'ERROR')) {
                    $lines = explode("\n", trim($response->body()));
                    $headers = str_getcsv(array_shift($lines), ';');
                    $competitors = [];

                    foreach ($lines as $line) {
                        if (empty(trim($line))) {
                            continue;
                        }
                        $values = str_getcsv($line, ';');
                        if (count($values) === count($headers)) {
                            $row = array_combine($headers, $values);
                            $competitors[] = [
                                'domain' => $row['Dn'] ?? '',
                                'competition_level' => (float) ($row['Cr'] ?? 0),
                                'common_keywords' => (int) ($row['Np'] ?? 0),
                                'organic_keywords' => (int) ($row['Or'] ?? 0),
                                'organic_traffic' => (int) ($row['Ot'] ?? 0),
                                'organic_cost' => (float) ($row['Oc'] ?? 0),
                                'adwords_keywords' => (int) ($row['Ad'] ?? 0),
                            ];
                        }
                    }

                    return [
                        'success' => true,
                        'domain' => $domain,
                        'competitors' => $competitors,
                    ];
                }

                return ['error' => 'Invalid response: '.substr($response->body(), 0, 100), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('SEMrush organic competitors error', ['error' => $e->getMessage()]);

                return ['error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Test API connection
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'SEMrush not configured'];
        }

        try {
            $response = Http::timeout(10)
                ->get($this->endpoint, [
                    'type' => 'domain_ranks',
                    'key' => $this->apiKey,
                    'domain' => 'semrush.com',
                    'database' => 'us',
                ]);

            if ($response->successful() && ! str_contains($response->body(), 'ERROR')) {
                return ['success' => true, 'message' => 'Connected successfully'];
            }

            return ['success' => false, 'message' => 'Connection failed: '.substr($response->body(), 0, 100)];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Error: '.$e->getMessage()];
        }
    }
}
