<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Istiqdam API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for integration with Saudi Government's Istiqdam platform
    | via Takamol / Qiwa API Gateway (gw-apic.qiwa.info)
    |
    */

    'environments' => [
        'staging' => [
            'base_url'      => env('ISTIQDAM_STAGE_BASE_URL', 'https://gw-apic.qiwa.info'),
            'oauth_url'     => env('ISTIQDAM_STAGE_OAUTH_URL', 'https://gw-apic.qiwa.info/takamol/staging/takamol-oauth/oauth2/token'),
            'client_id'     => env('ISTIQDAM_STAGE_CLIENT_ID', ''),
            'client_secret' => env('ISTIQDAM_STAGE_CLIENT_SECRET', ''),
            'api_key'       => env('ISTIQDAM_STAGE_API_KEY', ''),
            'scope'         => env('ISTIQDAM_STAGE_SCOPE', 'IntegrationScope'),
        ],
        'uat' => [
            'base_url'      => env('ISTIQDAM_UAT_BASE_URL', 'https://gw-apic.uat.qiwa.info'),
            'oauth_url'     => env('ISTIQDAM_UAT_OAUTH_URL', 'https://gw-apic.uat.qiwa.info/takamol/staging/takamol-oauth/oauth2/token'),
            'client_id'     => env('ISTIQDAM_UAT_CLIENT_ID', ''),
            'client_secret' => env('ISTIQDAM_UAT_CLIENT_SECRET', ''),
            'api_key'       => env('ISTIQDAM_UAT_API_KEY', ''),
            'scope'         => env('ISTIQDAM_UAT_SCOPE', 'IntegrationScope'),
        ],
        'production' => [
            'base_url'      => env('ISTIQDAM_PROD_BASE_URL', 'https://gw-apic.qiwa.sa'),
            'oauth_url'     => env('ISTIQDAM_PROD_OAUTH_URL', 'https://gw-apic.qiwa.sa/takamol/prod/takamol-oauth/oauth2/token'),
            'client_id'     => env('ISTIQDAM_PROD_CLIENT_ID', ''),
            'client_secret' => env('ISTIQDAM_PROD_CLIENT_SECRET', ''),
            'api_key'       => env('ISTIQDAM_PROD_API_KEY', ''),
            'scope'         => env('ISTIQDAM_PROD_SCOPE', 'IntegrationScope'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Current Environment
    |--------------------------------------------------------------------------
    | Options: 'staging', 'uat', 'production'
    */
    'current_env' => env('ISTIQDAM_ENV', 'staging'),

    /*
    |--------------------------------------------------------------------------
    | OAuth2 Token Cache
    |--------------------------------------------------------------------------
    | Token expires_in is 3600 seconds. Cache for 3500 to be safe.
    */
    'token_cache_key' => 'istiqdam_oauth_token',
    'token_cache_ttl' => 3500,

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    */
    'timeout' => 30,
    'connect_timeout' => 10,

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('ISTIQDAM_LOGGING', true),
        'channel' => 'istiqdam',
    ],
];
