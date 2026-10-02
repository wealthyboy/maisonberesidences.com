<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cloudbeds Booking Engine
    |--------------------------------------------------------------------------
    |
    | The public property code is still used by the current embedded booking
    | page while Maison Be moves the booking journey to the Cloudbeds API.
    |
    */
    'property_code' => env('CLOUDBEDS_PROPERTY_CODE', 'ef9dzW'),

    /*
    |--------------------------------------------------------------------------
    | Cloudbeds API
    |--------------------------------------------------------------------------
    |
    | Keep the API key server-side only. It must never be exposed to Blade,
    | JavaScript, the browser, or a public repository.
    |
    */
    'api' => [
        'key' => env('CLOUDBEDS_API_KEY'),
        'base_url' => env('CLOUDBEDS_API_BASE_URL', 'https://api.cloudbeds.com/api/v1.3'),
        'cache_seconds' => env('CLOUDBEDS_API_CACHE_SECONDS', 300),
    ],
];
