<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SEO Integrations - Free & Commercial API Hierarchy
    |--------------------------------------------------------------------------
    |
    | This configuration prioritizes FREE and organic capabilities before
    | commercial APIs. The system will use free tools first and only fall
    | back to paid APIs when necessary for advanced features.
    |
    | Tier Order: Organic/Built-in → Free APIs → Low-Cost → Commercial
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Organic / Built-in Capabilities (FREE, no API needed)
    |--------------------------------------------------------------------------
    |
    | These use our own crawling and analysis without external APIs.
    |
    */
    'organic' => [
        // Built-in website crawler for technical SEO audits
        'website_crawler' => [
            'enabled' => true,
            'max_pages_per_crawl' => (int) env('SEO_CRAWLER_MAX_PAGES', 100),
            'concurrent_requests' => (int) env('SEO_CRAWLER_CONCURRENCY', 3),
            'respect_robots_txt' => true,
            'user_agent' => 'Kre8ivDesigns-SEOBot/1.0',
            'features' => [
                'meta_analysis',
                'heading_structure',
                'image_alt_check',
                'internal_links',
                'page_speed_basic',
                'mobile_friendly_check',
                'structured_data_detection',
            ],
        ],

        // AI-powered content analysis (uses existing AI providers)
        'ai_content_analysis' => [
            'enabled' => true,
            'provider' => env('SEO_AI_PROVIDER', 'claude'), // uses configured AI
            'features' => [
                'keyword_suggestions',
                'content_optimization',
                'readability_analysis',
                'competitor_gap_analysis',
                'lsi_keywords',
            ],
        ],

        // Structured data validator (built-in)
        'schema_validator' => [
            'enabled' => true,
            'supported_types' => [
                'Organization', 'LocalBusiness', 'Product', 'Article',
                'FAQPage', 'HowTo', 'Recipe', 'Event', 'Review',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Free APIs (No cost or generous free tiers)
    |--------------------------------------------------------------------------
    */
    'free' => [
        // Google Search Console API - FREE (own data)
        // Provides: Search queries, impressions, clicks, positions for your sites
        'google_search_console' => [
            'enabled' => (bool) env('GSC_ENABLED', false),
            'client_id' => env('GOOGLE_CLIENT_ID', ''),
            'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
            'refresh_token' => env('GSC_REFRESH_TOKEN', ''),
            'endpoint' => 'https://www.googleapis.com/webmasters/v3',
            'features' => ['search_analytics', 'sitemaps', 'url_inspection'],
            'tier' => 'free',
        ],

        // Google PageSpeed Insights API - FREE (quota-based)
        // 25,000 queries/day for free
        'google_pagespeed' => [
            'enabled' => (bool) env('PAGESPEED_ENABLED', true),
            'api_key' => env('GOOGLE_PAGESPEED_API_KEY', ''),
            'endpoint' => 'https://www.googleapis.com/pagespeedonline/v5',
            'daily_limit' => 25000,
            'features' => ['core_web_vitals', 'lighthouse_scores', 'performance_metrics'],
            'tier' => 'free',
        ],

        // Bing Webmaster Tools API - FREE
        'bing_webmaster' => [
            'enabled' => (bool) env('BING_WEBMASTER_ENABLED', false),
            'api_key' => env('BING_WEBMASTER_API_KEY', ''),
            'endpoint' => 'https://ssl.bing.com/webmaster/api.svc',
            'features' => ['url_submission', 'crawl_stats', 'keyword_data'],
            'tier' => 'free',
        ],

        // Ubersuggest API - Limited free tier
        'ubersuggest' => [
            'enabled' => (bool) env('UBERSUGGEST_ENABLED', false),
            'api_key' => env('UBERSUGGEST_API_KEY', ''),
            'endpoint' => 'https://app.neilpatel.com/api',
            'free_tier_limit' => 3, // searches per day
            'features' => ['keyword_ideas', 'content_ideas', 'traffic_analyzer'],
            'tier' => 'freemium',
        ],

        // Keywords Everywhere - Free limited data
        'keywords_everywhere' => [
            'enabled' => (bool) env('KEYWORDS_EVERYWHERE_ENABLED', false),
            'api_key' => env('KEYWORDS_EVERYWHERE_API_KEY', ''),
            'endpoint' => 'https://api.keywordseverywhere.com/v1',
            'features' => ['search_volume', 'cpc', 'competition'],
            'tier' => 'freemium',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Low-Cost Commercial APIs (Under $100/month)
    |--------------------------------------------------------------------------
    */
    'low_cost' => [
        // DataForSEO - From $50/month, pay-per-use available
        // Most affordable for rank tracking & keyword data
        'dataforseo' => [
            'enabled' => (bool) env('DATAFORSEO_ENABLED', false),
            'login' => env('DATAFORSEO_LOGIN', ''),
            'password' => env('DATAFORSEO_PASSWORD', ''),
            'endpoint' => 'https://api.dataforseo.com/v3',
            'features' => [
                'serp_tracking',
                'keyword_data',
                'backlinks',
                'on_page_seo',
                'content_analysis',
                'domain_analytics',
            ],
            'tier' => 'low_cost',
            'pricing' => 'pay-per-use or from $50/mo',
        ],

        // SerpApi - From $50/month
        'serpapi' => [
            'enabled' => (bool) env('SERPAPI_ENABLED', false),
            'api_key' => env('SERPAPI_API_KEY', ''),
            'endpoint' => 'https://serpapi.com/search',
            'features' => ['serp_results', 'rank_tracking', 'local_results'],
            'tier' => 'low_cost',
            'pricing' => 'from $50/mo for 5,000 searches',
        ],

        // Mangools (KWFinder) - From $29.90/month
        'mangools' => [
            'enabled' => (bool) env('MANGOOLS_ENABLED', false),
            'api_key' => env('MANGOOLS_API_KEY', ''),
            'endpoint' => 'https://api.mangools.com/v3',
            'features' => ['keyword_research', 'serp_analysis', 'backlink_checker'],
            'tier' => 'low_cost',
            'pricing' => 'from $29.90/mo',
        ],

        // SpyFu - From $39/month
        'spyfu' => [
            'enabled' => (bool) env('SPYFU_ENABLED', false),
            'api_key' => env('SPYFU_API_KEY', ''),
            'endpoint' => 'https://www.spyfu.com/apis',
            'features' => ['competitor_keywords', 'ppc_research', 'serp_history'],
            'tier' => 'low_cost',
            'pricing' => 'from $39/mo',
        ],

        // Majestic - From $49.99/month
        'majestic' => [
            'enabled' => (bool) env('MAJESTIC_ENABLED', false),
            'api_key' => env('MAJESTIC_API_KEY', ''),
            'endpoint' => 'https://api.majestic.com/api/json',
            'features' => ['trust_flow', 'citation_flow', 'backlink_history', 'anchor_text'],
            'tier' => 'low_cost',
            'pricing' => 'from $49.99/mo',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Commercial Enterprise APIs ($100+/month)
    |--------------------------------------------------------------------------
    |
    | Use only when advanced features are required. These provide the most
    | comprehensive data but at premium pricing.
    |
    */
    'commercial' => [
        // Moz Pro API - From $99/month
        'moz' => [
            'enabled' => (bool) env('MOZ_ENABLED', false),
            'access_id' => env('MOZ_ACCESS_ID', ''),
            'secret_key' => env('MOZ_SECRET_KEY', ''),
            'endpoint' => 'https://lsapi.seomoz.com/v2',
            'features' => [
                'domain_authority',
                'page_authority',
                'spam_score',
                'link_metrics',
                'keyword_explorer',
                'rank_tracking',
            ],
            'tier' => 'commercial',
            'pricing' => 'from $99/mo',
        ],

        // Ahrefs API - From $99/month (Lite), API from $399/mo
        'ahrefs' => [
            'enabled' => (bool) env('AHREFS_ENABLED', false),
            'api_key' => env('AHREFS_API_KEY', ''),
            'endpoint' => 'https://api.ahrefs.com/v3',
            'features' => [
                'domain_rating',
                'url_rating',
                'backlinks',
                'organic_keywords',
                'content_explorer',
                'rank_tracker',
                'site_audit',
            ],
            'tier' => 'commercial',
            'pricing' => 'from $99/mo (tool), API from $399/mo',
        ],

        // SEMrush API - From $129.95/month
        'semrush' => [
            'enabled' => (bool) env('SEMRUSH_ENABLED', false),
            'api_key' => env('SEMRUSH_API_KEY', ''),
            'endpoint' => 'https://api.semrush.com',
            'features' => [
                'domain_analytics',
                'keyword_analytics',
                'backlinks',
                'position_tracking',
                'site_audit',
                'content_analyzer',
                'competitor_research',
            ],
            'tier' => 'commercial',
            'pricing' => 'from $129.95/mo',
        ],

        // Screaming Frog SEO Spider (CLI/API) - £199/year
        'screaming_frog' => [
            'enabled' => (bool) env('SCREAMING_FROG_ENABLED', false),
            'license_key' => env('SCREAMING_FROG_LICENSE', ''),
            'cli_path' => env('SCREAMING_FROG_CLI_PATH', '/usr/bin/screamingfrogseospider'),
            'features' => ['deep_crawl', 'technical_audit', 'structured_data', 'javascript_rendering'],
            'tier' => 'commercial',
            'pricing' => '£199/year',
        ],

        // Conductor - Enterprise SEO platform
        'conductor' => [
            'enabled' => (bool) env('CONDUCTOR_ENABLED', false),
            'api_key' => env('CONDUCTOR_API_KEY', ''),
            'api_secret' => env('CONDUCTOR_API_SECRET', ''),
            'endpoint' => 'https://api.conductor.com/v3',
            'features' => ['enterprise_seo', 'content_intelligence', 'market_share'],
            'tier' => 'enterprise',
            'pricing' => 'custom enterprise',
        ],

        // BrightEdge - Enterprise SEO platform
        'brightedge' => [
            'enabled' => (bool) env('BRIGHTEDGE_ENABLED', false),
            'api_key' => env('BRIGHTEDGE_API_KEY', ''),
            'endpoint' => 'https://api.brightedge.com/v2',
            'features' => ['share_of_voice', 'content_iq', 'instant_rank'],
            'tier' => 'enterprise',
            'pricing' => 'custom enterprise',
        ],

        // seoClarity - Enterprise SEO platform
        'seoclarity' => [
            'enabled' => (bool) env('SEOCLARITY_ENABLED', false),
            'api_key' => env('SEOCLARITY_API_KEY', ''),
            'endpoint' => 'https://api.seoclarity.net/v2',
            'features' => ['rank_intelligence', 'content_optimization', 'site_clarity'],
            'tier' => 'enterprise',
            'pricing' => 'custom enterprise',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Priority / Fallback Chain
    |--------------------------------------------------------------------------
    |
    | Define which APIs to use for each feature, in order of preference.
    | System tries free/organic first, then escalates to commercial if needed.
    |
    */
    'priority' => [
        // Keyword research: AI first, then free APIs, then commercial
        'keyword_research' => [
            'ai_content_analysis',     // Free: built-in AI
            'google_search_console',   // Free: own data
            'ubersuggest',             // Freemium
            'keywords_everywhere',     // Freemium
            'mangools',                // Low-cost
            'dataforseo',              // Low-cost
            'semrush',                 // Commercial
            'ahrefs',                  // Commercial
        ],

        // Rank tracking: own crawling first, then APIs
        'rank_tracking' => [
            'google_search_console',   // Free: own site data
            'dataforseo',              // Low-cost
            'serpapi',                 // Low-cost
            'semrush',                 // Commercial
            'ahrefs',                  // Commercial
        ],

        // Backlink analysis: free where available, then commercial
        'backlinks' => [
            'google_search_console',   // Free: limited data
            'majestic',                // Low-cost
            'dataforseo',              // Low-cost
            'moz',                     // Commercial
            'ahrefs',                  // Commercial
            'semrush',                 // Commercial
        ],

        // Technical SEO audit: built-in first
        'site_audit' => [
            'website_crawler',         // Free: built-in
            'google_pagespeed',        // Free
            'schema_validator',        // Free: built-in
            'screaming_frog',          // Commercial (local)
            'semrush',                 // Commercial
            'ahrefs',                  // Commercial
        ],

        // Page speed / Core Web Vitals
        'page_speed' => [
            'google_pagespeed',        // Free
            'website_crawler',         // Free: basic metrics
            'dataforseo',              // Low-cost
        ],

        // Competitor analysis
        'competitor_analysis' => [
            'ai_content_analysis',     // Free: AI-powered
            'spyfu',                   // Low-cost
            'dataforseo',              // Low-cost
            'semrush',                 // Commercial
            'ahrefs',                  // Commercial
        ],

        // Content optimization
        'content_optimization' => [
            'ai_content_analysis',     // Free: built-in AI
            'website_crawler',         // Free: basic checks
            'dataforseo',              // Low-cost
            'semrush',                 // Commercial
        ],

        // Domain metrics (DA, DR, etc.)
        'domain_metrics' => [
            'majestic',                // Low-cost (Trust/Citation Flow)
            'moz',                     // Commercial (DA/PA)
            'ahrefs',                  // Commercial (DR/UR)
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduling Configuration
    |--------------------------------------------------------------------------
    */
    'schedule' => [
        'keyword_tracking_interval' => 'daily',
        'backlink_check_interval' => 'weekly',
        'site_audit_interval' => 'weekly',
        'page_speed_check_interval' => 'daily',
        'competitor_check_interval' => 'weekly',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting & Caching
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'keyword_data_ttl' => 86400,       // 24 hours
        'serp_data_ttl' => 3600,           // 1 hour
        'backlink_data_ttl' => 604800,     // 7 days
        'domain_metrics_ttl' => 86400,     // 24 hours
        'page_speed_ttl' => 86400,         // 24 hours
    ],

    'rate_limits' => [
        'requests_per_minute' => (int) env('SEO_RATE_LIMIT_PER_MIN', 30),
        'respect_api_limits' => true,
        'auto_throttle' => true,
    ],
];
