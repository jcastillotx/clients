<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Application Storage Disks (Local)
        |--------------------------------------------------------------------------
        |
        | These disks are used by specific parts of the application.
        | All are configured to use local storage by default.
        |
        */

        'documents' => [
            'driver' => 'local',
            'root' => storage_path('app/documents'),
            'visibility' => 'private',
            'throw' => false,
        ],

        'contracts' => [
            'driver' => 'local',
            'root' => storage_path('app/contracts'),
            'visibility' => 'private',
            'throw' => false,
        ],

        'invoices' => [
            'driver' => 'local',
            'root' => storage_path('app/invoices'),
            'visibility' => 'private',
            'throw' => false,
        ],

        'attachments' => [
            'driver' => 'local',
            'root' => storage_path('app/attachments'),
            'visibility' => 'private',
            'throw' => false,
        ],

        'reports' => [
            'driver' => 'local',
            'root' => storage_path('app/reports'),
            'visibility' => 'private',
            'throw' => false,
        ],

        'exports' => [
            'driver' => 'local',
            'root' => storage_path('app/exports'),
            'visibility' => 'private',
            'throw' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | External Storage Providers
        |--------------------------------------------------------------------------
        |
        | These are pre-configured external storage providers that can be used
        | by setting the appropriate environment variables. Switch to these
        | by updating the FILESYSTEM_DISK environment variable or by
        | configuring specific storage categories in System Settings.
        |
        */

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

        // DigitalOcean Spaces (S3-compatible)
        'do-spaces' => [
            'driver' => 's3',
            'key' => env('DO_SPACES_KEY'),
            'secret' => env('DO_SPACES_SECRET'),
            'region' => env('DO_SPACES_REGION', 'nyc3'),
            'bucket' => env('DO_SPACES_BUCKET'),
            'endpoint' => env('DO_SPACES_ENDPOINT', 'https://nyc3.digitaloceanspaces.com'),
            'use_path_style_endpoint' => false,
            'throw' => false,
        ],

        // Backblaze B2 (S3-compatible)
        'b2' => [
            'driver' => 's3',
            'key' => env('B2_KEY_ID'),
            'secret' => env('B2_APPLICATION_KEY'),
            'region' => env('B2_REGION', 'us-west-002'),
            'bucket' => env('B2_BUCKET'),
            'endpoint' => env('B2_ENDPOINT'),
            'use_path_style_endpoint' => false,
            'throw' => false,
        ],

        // Cloudflare R2 (S3-compatible)
        'r2' => [
            'driver' => 's3',
            'key' => env('R2_ACCESS_KEY_ID'),
            'secret' => env('R2_SECRET_ACCESS_KEY'),
            'region' => 'auto',
            'bucket' => env('R2_BUCKET'),
            'endpoint' => env('R2_ENDPOINT'),
            'use_path_style_endpoint' => false,
            'throw' => false,
        ],

        // MinIO (S3-compatible, self-hosted)
        'minio' => [
            'driver' => 's3',
            'key' => env('MINIO_KEY'),
            'secret' => env('MINIO_SECRET'),
            'region' => env('MINIO_REGION', 'us-east-1'),
            'bucket' => env('MINIO_BUCKET'),
            'endpoint' => env('MINIO_ENDPOINT', 'http://localhost:9000'),
            'use_path_style_endpoint' => true,
            'throw' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
