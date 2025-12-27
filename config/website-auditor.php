<?php

return [
    'crawl' => [
        'max_pages' => (int) env('WEBSITE_AUDIT_MAX_PAGES', 50),
        'respect_robots' => (bool) env('WEBSITE_AUDIT_RESPECT_ROBOTS', true),
        'max_link_checks' => (int) env('WEBSITE_AUDIT_MAX_LINK_CHECKS', 200),
    ],

    'integrations' => [
        'google_pagespeed' => [
            'api_key' => (string) env('GOOGLE_PAGESPEED_API_KEY', ''),
        ],

        // Placeholders for future integrations (optional; service will degrade gracefully)
        'search_console' => [
            'enabled' => (bool) env('GOOGLE_SEARCH_CONSOLE_ENABLED', false),
            'service_account_json' => (string) env('GOOGLE_SEARCH_CONSOLE_SERVICE_ACCOUNT_JSON', ''),
            'property' => (string) env('GOOGLE_SEARCH_CONSOLE_PROPERTY', ''),
        ],
        'webpagetest' => [
            'api_key' => (string) env('WEBPAGETEST_API_KEY', ''),
        ],
        'gtmetrix' => [
            'email' => (string) env('GTMETRIX_EMAIL', ''),
            'api_key' => (string) env('GTMETRIX_API_KEY', ''),
        ],
        'ahrefs' => [
            'api_key' => (string) env('AHREFS_API_KEY', ''),
        ],
        'semrush' => [
            'api_key' => (string) env('SEMRUSH_API_KEY', ''),
        ],
        'moz' => [
            'access_id' => (string) env('MOZ_ACCESS_ID', ''),
            'secret_key' => (string) env('MOZ_SECRET_KEY', ''),
        ],
    ],
];

