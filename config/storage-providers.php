<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dropbox OAuth (required for Dropbox provider)
    |--------------------------------------------------------------------------
    */
    'dropbox' => [
        'app_key' => env('DROPBOX_APP_KEY'),
        'app_secret' => env('DROPBOX_APP_SECRET'),
        'redirect_uri' => env('DROPBOX_REDIRECT_URI'),
    ],
    'google_drive' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Providers
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'aws_s3' => [
            'label' => 'AWS S3',
            'auth' => [
                // Placeholder keys; implementation will define exact requirements.
                'required_credentials' => ['key', 'secret', 'region', 'bucket'],
            ],
            'endpoints' => [
                'console' => 'https://s3.console.aws.amazon.com/s3/home',
            ],
        ],

        'dropbox' => [
            'label' => 'Dropbox',
            'auth' => [
                'scopes' => [
                    // Placeholder scopes
                    'files.content.read',
                    'files.content.write',
                    'files.metadata.read',
                    'sharing.read',
                    'sharing.write',
                ],
            ],
            'endpoints' => [
                'api' => 'https://api.dropboxapi.com/2',
                'content' => 'https://content.dropboxapi.com/2',
            ],
        ],

        'google_drive' => [
            'label' => 'Google Drive',
            'auth' => [
                'scopes' => [
                    // Placeholder scopes
                    'https://www.googleapis.com/auth/drive.file',
                    'https://www.googleapis.com/auth/drive.metadata.readonly',
                ],
            ],
            'endpoints' => [
                'api' => 'https://www.googleapis.com/drive/v3',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Policies
    |--------------------------------------------------------------------------
    */
    'allowed_mime_types' => [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'image/jpeg',
        'image/png',
        'application/zip',
        'text/plain',
    ],

    // Bytes
    'file_size_limits' => [
        'aws_s3' => 1024 * 1024 * 1024 * 5, // 5GB (multipart capable)
        'dropbox' => 1024 * 1024 * 350,     // 350MB (placeholder)
        'google_drive' => 1024 * 1024 * 512, // 512MB (placeholder)
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Settings
    |--------------------------------------------------------------------------
    */
    'sync' => [
        'frequency_minutes' => env('STORAGE_SYNC_FREQUENCY_MINUTES', 15),
        'max_files_per_run' => env('STORAGE_SYNC_MAX_FILES_PER_RUN', 500),
    ],
];
