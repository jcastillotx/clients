<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Definitions
    |--------------------------------------------------------------------------
    |
    | Define all available features in the system. These can be assigned to
    | clients based on their tier, contract, or explicitly enabled/disabled.
    |
    */

    'available' => [
        // Core Features
        'dashboard' => [
            'name' => 'Dashboard Access',
            'description' => 'Access to client dashboard',
            'category' => 'core',
        ],
        'documents' => [
            'name' => 'Document Management',
            'description' => 'Upload, view, and manage documents',
            'category' => 'core',
        ],
        'invoices' => [
            'name' => 'Invoice Management',
            'description' => 'View and pay invoices',
            'category' => 'core',
        ],
        'service_requests' => [
            'name' => 'Service Requests',
            'description' => 'Submit and track service requests',
            'category' => 'core',
        ],

        // Brand Monitoring
        'brand_monitoring' => [
            'name' => 'Brand Monitoring',
            'description' => 'Track brand mentions across platforms',
            'category' => 'brand_monitoring',
        ],
        'brand_monitoring_news' => [
            'name' => 'News Monitoring',
            'description' => 'Monitor news mentions (NewsAPI + Google News)',
            'category' => 'brand_monitoring',
        ],
        'brand_monitoring_reviews' => [
            'name' => 'Review Monitoring',
            'description' => 'Monitor Yelp and Google reviews',
            'category' => 'brand_monitoring',
        ],
        'brand_monitoring_social' => [
            'name' => 'Social Media Monitoring',
            'description' => 'Monitor Reddit, YouTube, Twitter/X mentions',
            'category' => 'brand_monitoring',
        ],
        'brand_monitoring_web' => [
            'name' => 'Web Mention Monitoring',
            'description' => 'Monitor web mentions (Google, Bing)',
            'category' => 'brand_monitoring',
        ],
        'brand_monitoring_sentiment' => [
            'name' => 'AI Sentiment Analysis',
            'description' => 'Automated sentiment analysis of mentions',
            'category' => 'brand_monitoring',
        ],

        // AI Features
        'ai_assistant' => [
            'name' => 'AI Assistant',
            'description' => 'AI-powered chat assistant',
            'category' => 'ai',
        ],
        'ai_insights' => [
            'name' => 'AI Analytics & Insights',
            'description' => 'AI-generated insights and predictions',
            'category' => 'ai',
        ],
        'ai_document_analysis' => [
            'name' => 'AI Document Analysis',
            'description' => 'AI-powered document analysis and chat',
            'category' => 'ai',
        ],
        'ai_contract_generation' => [
            'name' => 'AI Contract Generation',
            'description' => 'Generate contracts using AI',
            'category' => 'ai',
        ],

        // Advanced Features
        'white_label_reports' => [
            'name' => 'White Label Reports',
            'description' => 'Branded custom reports',
            'category' => 'advanced',
        ],
        'api_access' => [
            'name' => 'API Access',
            'description' => 'RESTful API access to your data',
            'category' => 'advanced',
        ],
        'webhooks' => [
            'name' => 'Webhooks',
            'description' => 'Real-time webhook notifications',
            'category' => 'advanced',
        ],
        'cloud_storage' => [
            'name' => 'Cloud Storage Integration',
            'description' => 'Connect Google Drive, Dropbox, S3',
            'category' => 'advanced',
        ],
        'advanced_analytics' => [
            'name' => 'Advanced Analytics',
            'description' => 'Predictive analytics and insights',
            'category' => 'advanced',
        ],

        // Collaboration
        'team_collaboration' => [
            'name' => 'Team Collaboration',
            'description' => 'Multiple team members and collaboration tools',
            'category' => 'collaboration',
        ],
        'project_management' => [
            'name' => 'Project Management',
            'description' => 'Task boards, time tracking, budgets',
            'category' => 'collaboration',
        ],
        'meeting_scheduler' => [
            'name' => 'Meeting Scheduler',
            'description' => 'Schedule and manage meetings',
            'category' => 'collaboration',
        ],

        // Research & Consultation
        'research_assistant' => [
            'name' => 'Research Assistant',
            'description' => 'AI research and technical advisory',
            'category' => 'research',
        ],
        'competitor_monitoring' => [
            'name' => 'Competitor Monitoring',
            'description' => 'Track competitor websites and changes',
            'category' => 'research',
        ],
        'industry_insights' => [
            'name' => 'Industry Insights',
            'description' => 'Industry trends and insights',
            'category' => 'research',
        ],

        // Marketing Addons
        'competitor_analysis' => [
            'name' => 'Competitor Analysis',
            'description' => 'AI-powered comprehensive competitor analysis with SWOT, gaps, limitations, and strategic recommendations',
            'category' => 'marketing',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tier-Based Features
    |--------------------------------------------------------------------------
    |
    | Define which features are included in each tier. Higher tiers inherit
    | all features from lower tiers automatically.
    |
    */

    'tiers' => [
        'basic' => [
            // Core only
            'dashboard',
            'documents',
            'invoices',
            'service_requests',
        ],

        'standard' => [
            // Core + some extras
            'dashboard',
            'documents',
            'invoices',
            'service_requests',
            'ai_assistant',
            'meeting_scheduler',
            'cloud_storage',
        ],

        'professional' => [
            // Standard + brand monitoring basics
            'dashboard',
            'documents',
            'invoices',
            'service_requests',
            'ai_assistant',
            'ai_document_analysis',
            'meeting_scheduler',
            'cloud_storage',
            'brand_monitoring',
            'brand_monitoring_news',
            'brand_monitoring_reviews',
            'team_collaboration',
            'project_management',
        ],

        'premium' => [
            // Professional + full brand monitoring + research
            'dashboard',
            'documents',
            'invoices',
            'service_requests',
            'ai_assistant',
            'ai_insights',
            'ai_document_analysis',
            'ai_contract_generation',
            'meeting_scheduler',
            'cloud_storage',
            'brand_monitoring',
            'brand_monitoring_news',
            'brand_monitoring_reviews',
            'brand_monitoring_social',
            'brand_monitoring_web',
            'brand_monitoring_sentiment',
            'white_label_reports',
            'advanced_analytics',
            'team_collaboration',
            'project_management',
            'research_assistant',
            'competitor_monitoring',
            'competitor_analysis',
            'industry_insights',
        ],

        'enterprise' => [
            // Everything
            'dashboard',
            'documents',
            'invoices',
            'service_requests',
            'ai_assistant',
            'ai_insights',
            'ai_document_analysis',
            'ai_contract_generation',
            'meeting_scheduler',
            'cloud_storage',
            'brand_monitoring',
            'brand_monitoring_news',
            'brand_monitoring_reviews',
            'brand_monitoring_social',
            'brand_monitoring_web',
            'brand_monitoring_sentiment',
            'white_label_reports',
            'api_access',
            'webhooks',
            'advanced_analytics',
            'team_collaboration',
            'project_management',
            'research_assistant',
            'competitor_monitoring',
            'competitor_analysis',
            'industry_insights',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Limits
    |--------------------------------------------------------------------------
    |
    | Define usage limits for features based on tier
    |
    */

    'limits' => [
        'basic' => [
            'service_requests' => ['monthly' => 5],
            'documents' => ['storage_gb' => 1],
            'team_members' => ['max' => 1],
        ],

        'standard' => [
            'service_requests' => ['monthly' => 20],
            'documents' => ['storage_gb' => 10],
            'team_members' => ['max' => 3],
            'ai_assistant' => ['monthly_messages' => 100],
        ],

        'professional' => [
            'service_requests' => ['monthly' => 50],
            'documents' => ['storage_gb' => 50],
            'team_members' => ['max' => 10],
            'ai_assistant' => ['monthly_messages' => 500],
            'brand_monitoring' => ['tracked_keywords' => 10],
        ],

        'premium' => [
            'service_requests' => ['monthly' => null], // unlimited
            'documents' => ['storage_gb' => 250],
            'team_members' => ['max' => 25],
            'ai_assistant' => ['monthly_messages' => 2000],
            'brand_monitoring' => ['tracked_keywords' => 50],
            'competitor_analysis' => ['monthly_analyses' => 20],
        ],

        'enterprise' => [
            'service_requests' => ['monthly' => null], // unlimited
            'documents' => ['storage_gb' => null], // unlimited
            'team_members' => ['max' => null], // unlimited
            'ai_assistant' => ['monthly_messages' => null], // unlimited
            'brand_monitoring' => ['tracked_keywords' => null], // unlimited
            'competitor_analysis' => ['monthly_analyses' => null], // unlimited
            'api_access' => ['requests_per_minute' => 120],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Contract Type Feature Sets
    |--------------------------------------------------------------------------
    |
    | Predefined feature sets for common contract types
    |
    */

    'contract_types' => [
        'standard' => [
            // Same as standard tier
        ],

        'seo_package' => [
            'dashboard',
            'documents',
            'invoices',
            'brand_monitoring',
            'brand_monitoring_web',
            'competitor_monitoring',
            'research_assistant',
        ],

        'social_media_package' => [
            'dashboard',
            'documents',
            'invoices',
            'brand_monitoring',
            'brand_monitoring_social',
            'brand_monitoring_sentiment',
            'ai_insights',
        ],

        'reputation_management' => [
            'dashboard',
            'documents',
            'invoices',
            'brand_monitoring',
            'brand_monitoring_reviews',
            'brand_monitoring_news',
            'brand_monitoring_social',
            'brand_monitoring_sentiment',
        ],

        'full_service' => [
            // All features
            '*',
        ],
    ],
];
