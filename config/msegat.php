<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Msegat SMS Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for Msegat SMS Gateway.
    | You can set the URL, username, API key, sender ID, and encoding type.
    |
    */

    'url' => env('MSEGAT_URL', 'https://www.msegat.com/gw/sendsms.php'),
    'username' => env('MSEGAT_USERNAME', ''),
    'api_key' => env('MSEGAT_API_KEY', ''),
    'sender' => env('MSEGAT_SENDER', ''),
    'advertising_sender' => env('MSEGAT_ADVERTISING_SENDER', env('MSEGAT_SENDER', '')),
    'verification_sender' => env('MSEGAT_VERIFICATION_SENDER', env('MSEGAT_SENDER', '')),
    'default_sender_type' => env('MSEGAT_DEFAULT_SENDER_TYPE', 'advertising'),
    'encoding' => env('MSEGAT_ENCODING', 'UTF8'),
    'bulk_send_limit' => (int) env('MSEGAT_BULK_SEND_LIMIT', 200),
    'bulk_confirmation_keyword' => env('MSEGAT_BULK_CONFIRMATION_KEYWORD', 'SEND_ALL_SMS'),
];
