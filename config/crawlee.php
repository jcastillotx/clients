<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Crawlee Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the connection to the Crawlee microservice for advanced
    | web scraping capabilities including JavaScript rendering.
    |
    */

    // Base URL of the Crawlee microservice
    'base_url' => env('CRAWLEE_SERVICE_URL', 'http://127.0.0.1:3001'),

    // API key for authentication (must match the service's API_KEY)
    'api_key' => env('CRAWLEE_API_KEY'),

    // Request timeout in seconds
    'timeout' => env('CRAWLEE_TIMEOUT', 120),

    // Number of retries for failed requests
    'retries' => env('CRAWLEE_RETRIES', 3),

    /*
    |--------------------------------------------------------------------------
    | Default Crawling Options
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        // Maximum pages to crawl per request
        'max_requests' => env('CRAWLEE_DEFAULT_MAX_REQUESTS', 50),

        // Maximum concurrent requests
        'max_concurrency' => env('CRAWLEE_DEFAULT_CONCURRENCY', 5),

        // Default crawler type: 'cheerio' (fast) or 'playwright' (JS support)
        'crawler_type' => env('CRAWLEE_DEFAULT_CRAWLER', 'cheerio'),

        // Respect robots.txt by default
        'respect_robots' => true,

        // Follow same-domain links only by default
        'same_domain' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Crawler Types
    |--------------------------------------------------------------------------
    |
    | Available crawler types and their characteristics:
    |
    | cheerio:    Fast HTML parsing, no JS rendering, low resource usage
    | playwright: Full browser, JS rendering, screenshots, higher resource usage
    | puppeteer:  Same as playwright (uses Playwright internally)
    |
    */

    'crawlers' => [
        'cheerio' => [
            'description' => 'Fast HTTP crawler with Cheerio HTML parsing',
            'js_support' => false,
            'screenshots' => false,
            'recommended_for' => ['static sites', 'SEO audits', 'link crawling'],
        ],
        'playwright' => [
            'description' => 'Full browser crawler with JavaScript support',
            'js_support' => true,
            'screenshots' => true,
            'recommended_for' => ['SPAs', 'React/Vue apps', 'dynamic content'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */

    'features' => [
        // Enable Crawlee service integration
        'enabled' => env('CRAWLEE_ENABLED', true),

        // Fall back to built-in crawler if Crawlee service is unavailable
        'fallback_to_builtin' => env('CRAWLEE_FALLBACK', true),

        // Cache crawl results
        'cache_enabled' => env('CRAWLEE_CACHE_ENABLED', true),
        'cache_ttl' => env('CRAWLEE_CACHE_TTL', 3600), // 1 hour
    ],

];
