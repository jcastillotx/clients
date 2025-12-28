<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Livewire Class Namespace
    |--------------------------------------------------------------------------
    |
    | This app stores Livewire components in `app/Http/Livewire/*` and references
    | them using tags like <livewire:requests.request-show />.
    |
    */
    'class_namespace' => 'App\\Http\\Livewire',

    /*
    |--------------------------------------------------------------------------
    | Livewire View Path
    |--------------------------------------------------------------------------
    */
    'view_path' => resource_path('views/livewire'),

    /*
    |--------------------------------------------------------------------------
    | Layout View
    |--------------------------------------------------------------------------
    |
    | This is the default layout that will be used when rendering Livewire
    | components. You can override this per component.
    |
    */
    'layout' => 'layouts.app',

    /*
    |--------------------------------------------------------------------------
    | Lazy Loading
    |--------------------------------------------------------------------------
    |
    | Enable lazy loading for Livewire components.
    |
    */
    'lazy_loading' => true,

    /*
    |--------------------------------------------------------------------------
    | Temporary File Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Livewire handles file uploads by storing files in a temporary location
    | before they are validated and stored permanently.
    |
    */
    'temporary_file_upload' => [
        'disk' => 'local',
        'rules' => ['required', 'file', 'max:102400'], // 100MB max
        'directory' => 'livewire-tmp',
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma', 'pdf',
        ],
        'max_upload_time' => 5, // 5 minutes
        'cleanup' => true,
    ],
];
