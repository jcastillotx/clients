<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Brand Monitoring - Free & Commercial API Integrations
    |--------------------------------------------------------------------------
    |
    | This configuration uses a tiered approach: FREE APIs first, then
    | commercial APIs for advanced features. Free tiers provide enterprise-level
    | brand monitoring without expensive SaaS subscriptions.
    |
    | Priority Order: Free/Organic → Low-Cost → Commercial Enterprise
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
            'tier' => 'free',
        ],

        // Google News RSS (FREE, unlimited)
        'google_news_rss' => [
            'enabled' => (bool) env('GOOGLE_NEWS_RSS_ENABLED', true),
            'base_url' => 'https://news.google.com/rss/search',
            'tier' => 'free',
        ],

        // MediaStack - FREE: 500 requests/month
        // Paid: From $9.99/month for more requests
        'mediastack' => [
            'enabled' => (bool) env('MEDIASTACK_ENABLED', false),
            'api_key' => env('MEDIASTACK_API_KEY', ''),
            'endpoint' => 'http://api.mediastack.com/v1',
            'free_tier_limit' => 500, // per month
            'tier' => 'free',
        ],

        // GNews - FREE: 100 requests/day
        'gnews' => [
            'enabled' => (bool) env('GNEWS_ENABLED', false),
            'api_key' => env('GNEWS_API_KEY', ''),
            'endpoint' => 'https://gnews.io/api/v4',
            'free_tier_limit' => 100, // per day
            'tier' => 'free',
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

        // Trustpilot Business API
        'trustpilot' => [
            'enabled' => (bool) env('TRUSTPILOT_ENABLED', false),
            'api_key' => env('TRUSTPILOT_API_KEY', ''),
            'api_secret' => env('TRUSTPILOT_API_SECRET', ''),
            'endpoint' => 'https://api.trustpilot.com/v1',
        ],

        // G2 Crowd API
        'g2' => [
            'enabled' => (bool) env('G2_ENABLED', false),
            'api_key' => env('G2_API_KEY', ''),
            'endpoint' => 'https://data.g2.com/api/v1',
        ],

        // Capterra / Gartner Digital Markets API
        'capterra' => [
            'enabled' => (bool) env('CAPTERRA_ENABLED', false),
            'api_key' => env('CAPTERRA_API_KEY', ''),
            'endpoint' => 'https://api.capterra.com/v1',
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

        // Facebook Graph API - for page mentions monitoring
        'facebook' => [
            'enabled' => (bool) env('FACEBOOK_MENTIONS_ENABLED', false),
            'access_token' => env('FACEBOOK_PAGE_ACCESS_TOKEN', ''),
            'endpoint' => 'https://graph.facebook.com/v18.0',
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

    /*
    |--------------------------------------------------------------------------
    | Commercial Enterprise APIs (Premium Tier)
    |--------------------------------------------------------------------------
    |
    | These are commercial-grade brand monitoring platforms. Use only when
    | free tier capabilities are insufficient for your needs.
    |
    */
    'commercial' => [
        // Brandwatch - Enterprise brand intelligence platform
        // Pricing: Custom (typically $800-2000+/month)
        'brandwatch' => [
            'enabled' => (bool) env('BRANDWATCH_ENABLED', false),
            'api_key' => env('BRANDWATCH_API_KEY', ''),
            'api_secret' => env('BRANDWATCH_API_SECRET', ''),
            'project_id' => env('BRANDWATCH_PROJECT_ID', ''),
            'endpoint' => 'https://api.brandwatch.com',
            'tier' => 'enterprise',
            'features' => ['social_listening', 'sentiment', 'influencer_tracking', 'crisis_detection'],
        ],

        // Mention - Social listening & monitoring
        // Pricing: From $29/month (Starter) to $450+/month (Company)
        'mention' => [
            'enabled' => (bool) env('MENTION_ENABLED', false),
            'api_key' => env('MENTION_API_KEY', ''),
            'account_id' => env('MENTION_ACCOUNT_ID', ''),
            'endpoint' => 'https://api.mention.com/api',
            'tier' => 'commercial',
            'features' => ['mentions', 'sentiment', 'influencers', 'competitive_analysis'],
        ],

        // Brand24 - Online reputation monitoring
        // Pricing: From $49/month (Individual) to $399/month (Max)
        'brand24' => [
            'enabled' => (bool) env('BRAND24_ENABLED', false),
            'api_key' => env('BRAND24_API_KEY', ''),
            'project_id' => env('BRAND24_PROJECT_ID', ''),
            'endpoint' => 'https://api.brand24.com',
            'tier' => 'commercial',
            'features' => ['mentions', 'sentiment', 'influence_score', 'reach'],
        ],

        // Sprout Social - Social media management & analytics
        // Pricing: From $249/month (Standard) to $499/month (Advanced)
        'sprout_social' => [
            'enabled' => (bool) env('SPROUT_SOCIAL_ENABLED', false),
            'api_key' => env('SPROUT_SOCIAL_API_KEY', ''),
            'api_secret' => env('SPROUT_SOCIAL_API_SECRET', ''),
            'endpoint' => 'https://api.sproutsocial.com/v1',
            'tier' => 'commercial',
            'features' => ['publishing', 'engagement', 'analytics', 'listening'],
        ],

        // Meltwater - Media intelligence & social analytics
        // Pricing: Custom enterprise pricing
        'meltwater' => [
            'enabled' => (bool) env('MELTWATER_ENABLED', false),
            'api_key' => env('MELTWATER_API_KEY', ''),
            'api_secret' => env('MELTWATER_API_SECRET', ''),
            'endpoint' => 'https://api.meltwater.com/v3',
            'tier' => 'enterprise',
            'features' => ['media_monitoring', 'social_listening', 'influencer_db', 'analytics'],
        ],

        // Talkwalker - Social listening & analytics
        // Pricing: Custom (typically $9,600+/year)
        'talkwalker' => [
            'enabled' => (bool) env('TALKWALKER_ENABLED', false),
            'api_key' => env('TALKWALKER_API_KEY', ''),
            'project_id' => env('TALKWALKER_PROJECT_ID', ''),
            'endpoint' => 'https://api.talkwalker.com/api/v1',
            'tier' => 'enterprise',
            'features' => ['social_listening', 'image_recognition', 'sentiment', 'virality'],
        ],

        // Hootsuite Insights - Social listening (powered by Brandwatch)
        // Pricing: Part of Hootsuite Business/Enterprise plans
        'hootsuite_insights' => [
            'enabled' => (bool) env('HOOTSUITE_INSIGHTS_ENABLED', false),
            'access_token' => env('HOOTSUITE_ACCESS_TOKEN', ''),
            'endpoint' => 'https://platform.hootsuite.com/v1',
            'tier' => 'commercial',
            'features' => ['social_listening', 'sentiment', 'trends'],
        ],

        // Synthesio - AI-powered consumer intelligence
        // Pricing: Enterprise custom
        'synthesio' => [
            'enabled' => (bool) env('SYNTHESIO_ENABLED', false),
            'api_key' => env('SYNTHESIO_API_KEY', ''),
            'endpoint' => 'https://api.synthesio.com/v2',
            'tier' => 'enterprise',
            'features' => ['social_listening', 'ai_insights', 'trend_detection'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Priority Configuration
    |--------------------------------------------------------------------------
    |
    | Define which APIs to try first based on tier preference.
    | The system will fallback through tiers if primary APIs fail or hit limits.
    |
    */
    'priority' => [
        // For news monitoring: try free first, then commercial
        'news' => ['google_news_rss', 'newsapi', 'gnews', 'mediastack'],
        // For social monitoring: try free APIs, then commercial platforms
        'social' => ['reddit', 'youtube', 'twitter_rss', 'mention', 'brand24', 'brandwatch'],
        // For review monitoring: free review APIs first
        'reviews' => ['google_places', 'yelp', 'trustpilot', 'g2', 'capterra'],
        // For comprehensive monitoring: mix of free + commercial
        'comprehensive' => ['free_apis', 'mention', 'brand24', 'brandwatch', 'meltwater'],
    ],
];
