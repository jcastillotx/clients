<?php

namespace App\Services\Marketing\Crawling;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

/**
 * Laravel service for communicating with the Crawlee microservice.
 *
 * Provides a clean interface for web scraping with support for:
 * - Fast HTML crawling (Cheerio)
 * - JavaScript-rendered content (Playwright)
 * - Screenshot capture
 * - Structured data extraction
 * - Automatic fallback to built-in WebsiteCrawler
 */
class CrawleeService
{
    private string $baseUrl;
    private ?string $apiKey;
    private int $timeout;
    private int $retries;
    private bool $enabled;
    private bool $fallbackEnabled;
    private ?bool $serviceAvailable = null;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('crawlee.base_url', 'http://127.0.0.1:3001'), '/');
        $this->apiKey = config('crawlee.api_key');
        $this->timeout = config('crawlee.timeout', 120);
        $this->retries = config('crawlee.retries', 3);
        $this->enabled = config('crawlee.features.enabled', true);
        $this->fallbackEnabled = config('crawlee.features.fallback_to_builtin', true);
    }

    /**
     * Check if Crawlee integration is enabled in configuration.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Check if fallback to built-in crawler is enabled.
     */
    public function isFallbackEnabled(): bool
    {
        return $this->fallbackEnabled;
    }

    /**
     * Check if the Crawlee service is configured.
     */
    public function isConfigured(): bool
    {
        return $this->enabled && !empty($this->baseUrl);
    }

    /**
     * Check if the Crawlee service is healthy (cached for 30 seconds).
     */
    public function isHealthy(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        // Cache health check for 30 seconds to avoid hammering the service
        return Cache::remember('crawlee:health', 30, function () {
            return $this->checkHealth();
        });
    }

    /**
     * Perform actual health check against the service.
     */
    private function checkHealth(): bool
    {
        try {
            $response = Http::timeout(5)
                ->get("{$this->baseUrl}/health");

            $healthy = $response->successful() && ($response->json('status') === 'healthy');
            $this->serviceAvailable = $healthy;

            return $healthy;
        } catch (\Exception $e) {
            Log::debug('Crawlee service health check failed', ['error' => $e->getMessage()]);
            $this->serviceAvailable = false;
            return false;
        }
    }

    /**
     * Check if the service is available (uses cached result if available).
     */
    public function isAvailable(): bool
    {
        if ($this->serviceAvailable !== null) {
            return $this->serviceAvailable;
        }

        return $this->isHealthy();
    }

    /**
     * Get detailed health information from the service.
     */
    public function getHealthDetails(): array
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'Crawlee service is disabled',
                'enabled' => false,
            ];
        }

        try {
            $response = Http::timeout(5)
                ->get("{$this->baseUrl}/health/detailed");

            if ($response->successful()) {
                return array_merge($response->json(), ['enabled' => true]);
            }

            return ['success' => false, 'error' => 'Health check failed', 'enabled' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'enabled' => true];
        }
    }

    /**
     * Get current status summary.
     */
    public function getStatus(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'configured' => $this->isConfigured(),
            'healthy' => $this->isEnabled() ? $this->isHealthy() : false,
            'fallback_enabled' => $this->isFallbackEnabled(),
            'base_url' => $this->baseUrl,
        ];
    }

    /**
     * Smart crawl - uses Crawlee if available, falls back to built-in crawler.
     *
     * @param string $url Starting URL
     * @param array $options Crawl options
     * @return array
     */
    public function smartCrawl(string $url, array $options = []): array
    {
        // If Crawlee is enabled and available, use it
        if ($this->isEnabled() && $this->isAvailable()) {
            $result = $this->crawl($url, $options);

            // If successful, return the result
            if ($result['success'] ?? false) {
                return array_merge($result, ['crawler_used' => 'crawlee']);
            }

            // If Crawlee failed and fallback is disabled, return error
            if (!$this->fallbackEnabled) {
                return $result;
            }

            Log::info('Crawlee crawl failed, falling back to built-in crawler', [
                'url' => $url,
                'error' => $result['error'] ?? 'Unknown error',
            ]);
        }

        // Use built-in crawler as fallback
        return $this->fallbackCrawl($url, $options);
    }

    /**
     * Crawl using the built-in WebsiteCrawler.
     */
    public function fallbackCrawl(string $url, array $options = []): array
    {
        try {
            $crawler = new WebsiteCrawler();

            $crawlOptions = [
                'max_pages' => $options['max_requests'] ?? config('crawlee.defaults.max_requests', 50),
                'timeout_seconds' => min(60, $this->timeout),
                'respect_robots' => $options['respect_robots'] ?? true,
            ];

            $result = $crawler->crawl($url, $crawlOptions);

            // Transform to match Crawlee response format
            return [
                'success' => true,
                'crawler_type' => 'builtin',
                'crawler_used' => 'builtin',
                'total_pages' => count($result['pages'] ?? []),
                'results' => $result['pages'] ?? [],
                'graph' => $result['graph'] ?? [],
                'robots' => $result['robots'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('Built-in crawler failed', ['url' => $url, 'error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Both Crawlee and built-in crawler failed: ' . $e->getMessage(),
                'crawler_used' => 'builtin',
            ];
        }
    }

    /**
     * Crawl a website starting from a URL.
     *
     * @param string|array $urls Starting URL(s)
     * @param array $options Crawl options
     * @return array
     */
    public function crawl(string|array $urls, array $options = []): array
    {
        if (!$this->isEnabled()) {
            if ($this->fallbackEnabled) {
                $url = is_array($urls) ? $urls[0] : $urls;
                return $this->fallbackCrawl($url, $options);
            }

            return [
                'success' => false,
                'error' => 'Crawlee service is disabled. Enable it in config/crawlee.php or .env',
            ];
        }

        $payload = $this->buildCrawlPayload($urls, $options);
        $result = $this->request('POST', '/api/v1/crawl/sync', $payload);

        // If connection failed and fallback is enabled, try built-in crawler
        if (($result['connection_error'] ?? false) && $this->fallbackEnabled) {
            $url = is_array($urls) ? $urls[0] : $urls;
            return $this->fallbackCrawl($url, $options);
        }

        return $result;
    }

    /**
     * Start an asynchronous crawl job.
     *
     * @param string|array $urls Starting URL(s)
     * @param array $options Crawl options
     * @return array Job info with job_id for status polling
     */
    public function crawlAsync(string|array $urls, array $options = []): array
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'Crawlee service is disabled. Async crawling requires Crawlee.',
            ];
        }

        $payload = $this->buildCrawlPayload($urls, $options);

        return $this->request('POST', '/api/v1/crawl', $payload);
    }

    /**
     * Get the status of an async crawl job.
     *
     * @param string $jobId Job ID returned from crawlAsync
     * @return array Job status and results
     */
    public function getJobStatus(string $jobId): array
    {
        return $this->request('GET', "/api/v1/crawl/{$jobId}");
    }

    /**
     * Cancel a running crawl job.
     *
     * @param string $jobId Job ID to cancel
     * @return array
     */
    public function cancelJob(string $jobId): array
    {
        return $this->request('DELETE', "/api/v1/crawl/{$jobId}");
    }

    /**
     * Scrape a single page.
     *
     * @param string $url Page URL
     * @param array $selectors CSS selectors to extract (key => selector)
     * @param array $options Scrape options
     * @return array
     */
    public function scrapePage(string $url, array $selectors = [], array $options = []): array
    {
        if (!$this->isEnabled()) {
            // For simple scraping, fallback can handle it
            if ($this->fallbackEnabled && empty($selectors)) {
                $result = $this->fallbackCrawl($url, ['max_requests' => 1]);
                return [
                    'success' => $result['success'] ?? false,
                    'data' => $result['results'][0] ?? null,
                    'crawler_used' => 'builtin',
                ];
            }

            return [
                'success' => false,
                'error' => 'Crawlee service is disabled. Custom selector extraction requires Crawlee.',
            ];
        }

        $payload = [
            'url' => $url,
            'selectors' => $selectors,
            'crawler_type' => $options['crawler_type'] ?? 'cheerio',
            'options' => $options,
        ];

        return $this->request('POST', '/api/v1/scrape', $payload);
    }

    /**
     * Scrape a JavaScript-rendered page using Playwright.
     *
     * @param string $url Page URL
     * @param array $selectors CSS selectors to extract
     * @param array $options Additional options
     * @return array
     */
    public function scrapeJsPage(string $url, array $selectors = [], array $options = []): array
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'Crawlee service is disabled. JavaScript rendering requires Crawlee with Playwright.',
            ];
        }

        return $this->scrapePage($url, $selectors, array_merge($options, [
            'crawler_type' => 'playwright',
        ]));
    }

    /**
     * Take a screenshot of a page.
     *
     * @param string $url Page URL
     * @param array $options Screenshot options
     * @return array Contains base64 encoded screenshot
     */
    public function screenshot(string $url, array $options = []): array
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'Crawlee service is disabled. Screenshots require Crawlee with Playwright.',
            ];
        }

        $payload = [
            'url' => $url,
            'options' => array_merge([
                'fullPage' => true,
                'type' => 'png',
            ], $options),
        ];

        return $this->request('POST', '/api/v1/screenshot', $payload, 60);
    }

    /**
     * Extract structured data from a page based on a schema.
     *
     * @param string $url Page URL
     * @param array $schema Extraction schema (field => selector)
     * @param array $options Extraction options
     * @return array
     */
    public function extract(string $url, array $schema, array $options = []): array
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'Crawlee service is disabled. Structured extraction requires Crawlee.',
            ];
        }

        $payload = [
            'url' => $url,
            'schema' => $schema,
            'crawler_type' => $options['crawler_type'] ?? 'cheerio',
            'options' => $options,
        ];

        return $this->request('POST', '/api/v1/extract', $payload);
    }

    /**
     * Extract data from a JavaScript-rendered page.
     *
     * @param string $url Page URL
     * @param array $schema Extraction schema
     * @param array $options Additional options
     * @return array
     */
    public function extractJs(string $url, array $schema, array $options = []): array
    {
        return $this->extract($url, $schema, array_merge($options, [
            'crawler_type' => 'playwright',
        ]));
    }

    /**
     * List all crawl jobs.
     *
     * @param string|null $status Filter by status (running, completed, failed)
     * @param int $limit Maximum number of jobs to return
     * @return array
     */
    public function listJobs(?string $status = null, int $limit = 50): array
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'error' => 'Crawlee service is disabled'];
        }

        $query = ['limit' => $limit];
        if ($status) {
            $query['status'] = $status;
        }

        return $this->request('GET', '/api/v1/jobs', $query);
    }

    /**
     * Crawl a website for SEO analysis.
     *
     * Convenience method that configures crawling for SEO purposes.
     * Automatically falls back to built-in crawler if Crawlee unavailable.
     *
     * @param string $url Website URL
     * @param int $maxPages Maximum pages to crawl
     * @return array
     */
    public function crawlForSeo(string $url, int $maxPages = 50): array
    {
        return $this->smartCrawl($url, [
            'max_requests' => $maxPages,
            'follow_links' => true,
            'same_domain' => true,
            'crawler_type' => 'cheerio',
        ]);
    }

    /**
     * Crawl a SPA/JavaScript-heavy website.
     *
     * @param string $url Website URL
     * @param int $maxPages Maximum pages to crawl
     * @param string|null $waitForSelector Wait for this selector before scraping
     * @return array
     */
    public function crawlSpa(string $url, int $maxPages = 20, ?string $waitForSelector = null): array
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'Crawlee service is disabled. SPA crawling requires Crawlee with Playwright.',
            ];
        }

        $options = [
            'max_requests' => $maxPages,
            'follow_links' => true,
            'same_domain' => true,
            'crawler_type' => 'playwright',
        ];

        if ($waitForSelector) {
            $options['wait_for_selector'] = $waitForSelector;
        }

        return $this->crawl($url, $options);
    }

    /**
     * Build the payload for crawl requests.
     */
    private function buildCrawlPayload(string|array $urls, array $options): array
    {
        $urls = is_array($urls) ? $urls : [$urls];

        return [
            'urls' => $urls,
            'crawler_type' => $options['crawler_type'] ?? 'cheerio',
            'options' => [
                'maxRequestsPerCrawl' => $options['max_requests'] ?? config('crawlee.defaults.max_requests', 50),
                'maxConcurrency' => $options['max_concurrency'] ?? config('crawlee.defaults.max_concurrency', 5),
                'followLinks' => $options['follow_links'] ?? true,
                'sameDomain' => $options['same_domain'] ?? true,
                'respectRobotsTxt' => $options['respect_robots'] ?? true,
                'waitForSelector' => $options['wait_for_selector'] ?? null,
                'waitForTimeout' => $options['wait_timeout'] ?? 2000,
                'selectors' => $options['selectors'] ?? [],
                'headers' => $options['headers'] ?? [],
            ],
        ];
    }

    /**
     * Make an HTTP request to the Crawlee service.
     */
    private function request(string $method, string $endpoint, array $data = [], ?int $timeout = null): array
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'Crawlee service is disabled',
            ];
        }

        $url = $this->baseUrl . $endpoint;
        $timeout = $timeout ?? $this->timeout;

        try {
            $http = Http::timeout($timeout)
                ->retry($this->retries, 1000, function ($exception) {
                    // Only retry on connection/timeout errors
                    return $exception instanceof ConnectionException;
                });

            // Add API key if configured
            if ($this->apiKey) {
                $http = $http->withHeaders(['X-API-Key' => $this->apiKey]);
            }

            // Make the request
            $response = match (strtoupper($method)) {
                'GET' => $http->get($url, $data),
                'POST' => $http->post($url, $data),
                'DELETE' => $http->delete($url, $data),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            if ($response->successful()) {
                return $response->json();
            }

            // Handle error responses
            $error = $response->json('error') ?? $response->body();
            Log::error('Crawlee service error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'error' => $error,
            ]);

            return [
                'success' => false,
                'error' => $error,
                'status_code' => $response->status(),
            ];

        } catch (ConnectionException $e) {
            Log::warning('Crawlee service connection failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            // Clear health cache on connection error
            Cache::forget('crawlee:health');
            $this->serviceAvailable = false;

            return [
                'success' => false,
                'error' => 'Failed to connect to Crawlee service. Is it running?',
                'connection_error' => true,
            ];

        } catch (RequestException $e) {
            Log::error('Crawlee service request failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];

        } catch (\Exception $e) {
            Log::error('Crawlee service unexpected error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'An unexpected error occurred: ' . $e->getMessage(),
            ];
        }
    }
}
