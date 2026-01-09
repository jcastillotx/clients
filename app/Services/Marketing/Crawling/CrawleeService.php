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
 */
class CrawleeService
{
    private string $baseUrl;
    private ?string $apiKey;
    private int $timeout;
    private int $retries;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('crawlee.base_url', 'http://127.0.0.1:3001'), '/');
        $this->apiKey = config('crawlee.api_key');
        $this->timeout = config('crawlee.timeout', 120);
        $this->retries = config('crawlee.retries', 3);
    }

    /**
     * Check if the Crawlee service is configured and available.
     */
    public function isConfigured(): bool
    {
        return !empty($this->baseUrl);
    }

    /**
     * Check if the Crawlee service is healthy.
     */
    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(5)
                ->get("{$this->baseUrl}/health");

            return $response->successful() && ($response->json('status') === 'healthy');
        } catch (\Exception $e) {
            Log::warning('Crawlee service health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get detailed health information from the service.
     */
    public function getHealthDetails(): array
    {
        try {
            $response = Http::timeout(5)
                ->get("{$this->baseUrl}/health/detailed");

            if ($response->successful()) {
                return $response->json();
            }

            return ['success' => false, 'error' => 'Health check failed'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
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
        $payload = $this->buildCrawlPayload($urls, $options);

        return $this->request('POST', '/api/v1/crawl/sync', $payload);
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
     *
     * @param string $url Website URL
     * @param int $maxPages Maximum pages to crawl
     * @return array
     */
    public function crawlForSeo(string $url, int $maxPages = 50): array
    {
        return $this->crawl($url, [
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
            Log::error('Crawlee service connection failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

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
