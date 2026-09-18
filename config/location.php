<?php

return [
    /*
    | The package defaults to a fixed US testing address. Keep that behavior
    | opt-in so real visitors are located using their actual request IP.
    */
    'testing' => [
        'ip' => env('LOCATION_TESTING_IP', '66.102.0.0'),
        'enabled' => env('LOCATION_TESTING', false),
    ],
];
