<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Istiqdam API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for integration with Saudi Government's Istiqdam platform
    |
    */

    'environments' => [
        'stage' => [
            'base_url' => 'https://api.lis-data-middleware.stage.devops.takamol.support',
            'api_key' => 'WzOAdSqiI4x6s8QNraBRdbxrvQgpMOxFkkW6dcJ8U1I',
            'client_id' => 'test-istiqdam',
        ],
        'production' => [
            'base_url' => env('ISTIQDAM_PROD_BASE_URL', ''),
            'api_key' => env('ISTIQDAM_PROD_API_KEY', ''),
            'client_id' => env('ISTIQDAM_PROD_CLIENT_ID', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Current Environment
    |--------------------------------------------------------------------------
    |
    | Set which environment to use: 'stage' or 'production'
    |
    */
    'current_env' => env('ISTIQDAM_ENV', 'stage'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout for API requests in seconds
    |
    */
    'timeout' => 30,

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable/disable logging of API requests and responses
    |
    */
    'logging' => [
        'enabled' => env('ISTIQDAM_LOGGING', true),
        'channel' => 'istiqdam',
    ],
];
