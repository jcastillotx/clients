<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Web Push (VAPID)
    |--------------------------------------------------------------------------
    |
    | Generate VAPID keys and set them in your environment.
    |
    | - PWA_VAPID_PUBLIC_KEY: URL-safe base64 public key
    | - PWA_VAPID_PRIVATE_KEY: URL-safe base64 private key
    |
    */
    'vapid_public_key' => env('PWA_VAPID_PUBLIC_KEY', ''),
    'vapid_private_key' => env('PWA_VAPID_PRIVATE_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Default notification options
    |--------------------------------------------------------------------------
    */
    'default_ttl' => (int) env('PWA_PUSH_TTL', 3600),
];

