<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IP Allowlist
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of allowed IPs/CIDRs for admin routes.
    | Examples:
    | - "203.0.113.10"
    | - "203.0.113.0/24,198.51.100.2"
    |
    | Empty/null means "allow all".
    |
    */
    'admin_ip_allowlist' => array_values(array_filter(array_map('trim', explode(',', (string) env('ADMIN_IP_ALLOWLIST', ''))))),

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    */
    'enforce_admin_2fa' => (bool) env('ENFORCE_ADMIN_2FA', true),
    'two_factor_issuer' => (string) env('TWO_FACTOR_ISSUER', env('APP_NAME', 'Client Portal')),

    /*
    |--------------------------------------------------------------------------
    | Audit & Compliance
    |--------------------------------------------------------------------------
    */
    'audit_retention_days' => (int) env('AUDIT_RETENTION_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | TLS / HTTPS Configuration
    |--------------------------------------------------------------------------
    |
    | SOC2 Type II compliant TLS 1.3 configuration for data in transit.
    |
    */
    'force_https' => (bool) env('FORCE_HTTPS', true),
    'enforce_tls_local' => (bool) env('ENFORCE_TLS_LOCAL', false),

    // HTTP Strict Transport Security (HSTS)
    'hsts_enabled' => (bool) env('HSTS_ENABLED', true),
    'hsts_max_age' => (int) env('HSTS_MAX_AGE', 31536000), // 1 year
    'hsts_include_subdomains' => (bool) env('HSTS_INCLUDE_SUBDOMAINS', true),
    'hsts_preload' => (bool) env('HSTS_PRELOAD', false),

    // Certificate Transparency
    'expect_ct_enabled' => (bool) env('EXPECT_CT_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Encryption at Rest (AES-256)
    |--------------------------------------------------------------------------
    |
    | SOC2 Type II compliant AES-256 encryption for data at rest.
    |
    */
    'encryption' => [
        'algorithm' => 'aes-256-gcm',
        'key_derivation' => 'sha256',
        'key_derivation_iterations' => (int) env('ENCRYPTION_KEY_ITERATIONS', 100000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Room Security
    |--------------------------------------------------------------------------
    */
    'data_rooms' => [
        'require_2fa_default' => (bool) env('DATA_ROOM_REQUIRE_2FA', true),
        'session_timeout_minutes' => (int) env('DATA_ROOM_SESSION_TIMEOUT', 30),
        'allow_download_default' => (bool) env('DATA_ROOM_ALLOW_DOWNLOAD', true),
        'max_file_size_mb' => (int) env('DATA_ROOM_MAX_FILE_SIZE_MB', 500),
        'allowed_mime_types' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'text/plain',
            'text/csv',
            'application/zip',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | S3 Encrypted Storage
    |--------------------------------------------------------------------------
    |
    | Configuration for encrypted S3 storage with SSE-S3 or SSE-KMS.
    |
    */
    's3_encryption' => [
        'enabled' => (bool) env('S3_ENCRYPTION_ENABLED', true),
        'type' => env('S3_ENCRYPTION_TYPE', 'sse-s3'), // sse-s3, sse-kms
        'kms_key_id' => env('S3_KMS_KEY_ID'),
        'bucket' => env('S3_ENCRYPTED_BUCKET', env('AWS_BUCKET')),
    ],
];
