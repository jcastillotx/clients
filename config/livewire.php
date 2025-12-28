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
];
