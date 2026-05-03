<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
    ],

    'taqnyat' => [
        'sms' => [
            'base_url' => env('TAQNYAT_SMS_BASE_URL', env('TAQNYAT_BASE_URL', 'https://api.taqnyat.sa')),
            'bearer_token' => env('TAQNYAT_SMS_BEARER_TOKEN', env('TAQNYAT_BEARER', '')),
            'bearer' => env('TAQNYAT_SMS_BEARER_TOKEN', env('TAQNYAT_BEARER', '')),
        ],
    ],

    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'meta'),
        'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v23.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
        'default_country_code' => env('WHATSAPP_DEFAULT_COUNTRY_CODE', '966'),
        'timeout' => (int) env('WHATSAPP_TIMEOUT', 15),
        'bulk_send_limit' => (int) env('WHATSAPP_BULK_SEND_LIMIT', 200),
        'bulk_confirmation_keyword' => env('WHATSAPP_BULK_CONFIRMATION_KEYWORD', 'SEND_ALL_WHATSAPP'),
        'default_purpose' => env('WHATSAPP_DEFAULT_PURPOSE', 'verification'),
        'allow_advertising' => filter_var(env('WHATSAPP_ALLOW_ADVERTISING', false), FILTER_VALIDATE_BOOLEAN),
        'ultramsg' => [
            'api_url' => env('ULTRAMSG_API_URL', 'https://api.ultramsg.com'),
            'default_instance_key' => env('ULTRAMSG_DEFAULT_INSTANCE_KEY', ''),
            'instances' => [],
        ],
    ],

];
