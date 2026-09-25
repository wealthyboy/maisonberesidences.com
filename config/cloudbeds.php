<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cloudbeds Booking Engine
    |--------------------------------------------------------------------------
    |
    | The property code is public-facing (it also appears in the hosted
    | Cloudbeds booking URL), but keeping it in config makes it easy to change
    | without touching the booking view.
    |
    */
    'property_code' => env('CLOUDBEDS_PROPERTY_CODE', 'ef9dzW'),
];
