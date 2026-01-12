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
    'encoding' => env('MSEGAT_ENCODING', 'UTF8'),
];