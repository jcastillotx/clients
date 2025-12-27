<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Brand Monitoring - Free & Low-Cost API Integrations
    |--------------------------------------------------------------------------
    |
    | This configuration uses free API tiers to provide enterprise-level
    | brand monitoring capabilities without expensive SaaS subscriptions.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | News & Press Monitoring
    |--------------------------------------------------------------------------
    */
    'news' => [
        // NewsAPI.org - FREE: 100 requests/day, 1 month history
        // Paid: $449/mo for 250k requests
        'newsapi' => [
            'enabled' => (bool) env('NEWSAPI_ENABLED', true),
            'api_key' => env('NEWSAPI_API_KEY', ''),
            'free_tier_limit' => 100, // requests per day
            'endpoint' => 'https://newsapi.org/v2',
        ],

        // Google News RSS (FREE, unlimited)
        'google_news_rss' => [
            'enabled' => (bool) env('GOOGLE_NEWS_RSS_ENABLED', true),
            'base_url' => 'https://news.google.com/rss/search',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Review Monitoring
    |--------------------------------------------------------------------------
    */
    'reviews' => [
        // Yelp Fusion API - FREE: 5000 requests/day
        'yelp' => [
            'enabled' => (bool) env('YELP_API_ENABLED', true),
            'api_key' => env('YELP_API_KEY', ''),
            'endpoint' => 'https://api.yelp.com/v3',
            'free_tier_limit' => 5000, // per day
        ],

        // Google Places API - FREE: $200 credit/month (~40k searches)
        'google_places' => [
            'enabled' => (bool) env('GOOGLE_PLACES_ENABLED', true),
            'api_key' => env('GOOGLE_PLACES_API_KEY', ''),
            'endpoint' => 'https://maps.googleapis.com/maps/api/place',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Media Monitoring
    |--------------------------------------------------------------------------
    */
    'social' => [
        // Reddit API - FREE, rate limited to 60 requests/minute
        'reddit' => [
            'enabled' => (bool) env('REDDIT_API_ENABLED', true),
            'client_id' => env('REDDIT_CLIENT_ID', ''),
            'client_secret' => env('REDDIT_CLIENT_SECRET', ''),
            'user_agent' => env('REDDIT_USER_AGENT', 'BrandMonitor/1.0'),
            'endpoint' => 'https://oauth.reddit.com',
        ],

        // YouTube Data API - FREE: 10,000 quota units/day (~$0.00)
        // Paid: $0 (YouTube is generous with free tier)
        'youtube' => [
            'enabled' => (bool) env('YOUTUBE_API_ENABLED', true),
            'api_key' => env('YOUTUBE_API_KEY', ''),
            'endpoint' => 'https://www.googleapis.com/youtube/v3',
            'free_tier_quota' => 10000, // units per day
        ],

        // Twitter/X API - FREE (Basic tier): Very limited
        // Use RSS/nitter.net as alternative
        'twitter_rss' => [
            'enabled' => (bool) env('TWITTER_RSS_ENABLED', true),
            'nitter_instances' => [
                'https://nitter.net',
                'https://nitter.poast.org',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Web Mention Monitoring
    |--------------------------------------------------------------------------
    */
    'web_mentions' => [
        // Google Custom Search API - FREE: 100 queries/day
        // Paid: $5 per 1000 queries (max 10k/day)
        'google_search' => [
            'enabled' => (bool) env('GOOGLE_SEARCH_ENABLED', true),
            'api_key' => env('GOOGLE_SEARCH_API_KEY', ''),
            'search_engine_id' => env('GOOGLE_SEARCH_ENGINE_ID', ''),
            'endpoint' => 'https://www.googleapis.com/customsearch/v1',
            'free_tier_limit' => 100, // per day
        ],

        // Bing Search API - FREE: 1000 queries/month
        'bing_search' => [
            'enabled' => (bool) env('BING_SEARCH_ENABLED', true),
            'api_key' => env('BING_SEARCH_API_KEY', ''),
            'endpoint' => 'https://api.bing.microsoft.com/v7.0/search',
            'free_tier_limit' => 1000, // per month
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | RSS Feed Monitoring
    |--------------------------------------------------------------------------
    */
    'rss' => [
        'enabled' => (bool) env('RSS_MONITORING_ENABLED', true),
        'check_interval_minutes' => (int) env('RSS_CHECK_INTERVAL', 60),
        'max_items_per_feed' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Schedule
    |--------------------------------------------------------------------------
    */
    'schedule' => [
        // How often to check for new mentions (in minutes)
        'news_check_interval' => 60, // hourly
        'review_check_interval' => 360, // every 6 hours
        'social_check_interval' => 30, // every 30 minutes
        'web_mention_interval' => 120, // every 2 hours
        'rss_check_interval' => 60, // hourly
    ],

    /*
    |--------------------------------------------------------------------------
    | Sentiment Analysis
    |--------------------------------------------------------------------------
    */
    'sentiment' => [
        // Use existing AI providers (already configured)
        'provider' => env('SENTIMENT_AI_PROVIDER', 'openai'),
        'model' => env('SENTIMENT_AI_MODEL', 'gpt-4o-mini'),

        // Batch sentiment analysis to save costs
        'batch_size' => 50, // analyze 50 mentions at once
        'batch_interval_minutes' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Thresholds
    |--------------------------------------------------------------------------
    */
    'alerts' => [
        'negative_sentiment_threshold' => -0.5, // -1 to 1 scale
        'mention_spike_threshold' => 200, // % increase
        'notify_on_critical_mentions' => true,
    ],
];
