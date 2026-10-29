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
        'chatwoot' => [
            'base_url' => env('CHATWOOT_BASE_URL', 'https://chat.widers.net'),
            'account_id' => env('CHATWOOT_ACCOUNT_ID'),
            'inbox_id' => env('CHATWOOT_INBOX_ID'),
            'access_token' => env('CHATWOOT_ACCESS_TOKEN'),
        ],
        'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v23.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
        'app_id' => env('WHATSAPP_APP_ID'),
        'configuration_id' => env('WHATSAPP_CONFIGURATION_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
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

    'moyasar' => [
        'enabled'        => env('MOYASAR_ENABLED', false),
        'secret_key'     => env('MOYASAR_SECRET_KEY', ''),
        'publishable_key'=> env('MOYASAR_PUBLISHABLE_KEY', ''),
        'webhook_secret' => env('MOYASAR_WEBHOOK_SECRET', ''),
        'base_url'       => env('MOYASAR_BASE_URL', 'https://api.moyasar.com/v1'),
        // Mobile WebViews depend on these callback paths and on the production HTTPS host.
        'app_callback_base_url' => env('MOYASAR_APP_CALLBACK_BASE_URL', 'https://dashboard.platinumeastern.com.sa'),
    ],

    // نطاقات المواقع الخارجية المسموح بالعودة إليها بعد دفع حجز الساعات (مفصولة بفواصل).
    // فارغة = تُقبل أي صفحة http(s) يرسلها الموقع الخارجي.
    'hourly_website' => [
        'return_hosts' => array_values(array_filter(array_map('trim', explode(',', (string) env('HOURLY_WEBSITE_RETURN_HOSTS', ''))))),
    ],

    'zoho_salesiq' => [
        'salesiq_base_url' => env('ZOHO_SALESIQ_BASE_URL', 'https://salesiq.zoho.sa'),
        'accounts_base_url' => env('ZOHO_SALESIQ_ACCOUNTS_BASE_URL', 'https://accounts.zoho.sa'),
        'screen_name' => env('ZOHO_SALESIQ_SCREEN_NAME', 'platinumeastern'),
        'client_id' => env('ZOHO_SALESIQ_CLIENT_ID', ''),
        'client_secret' => env('ZOHO_SALESIQ_CLIENT_SECRET', ''),
        'refresh_token' => env('ZOHO_SALESIQ_REFRESH_TOKEN', ''),
        'widget_url' => env('ZOHO_SALESIQ_WIDGET_URL', 'https://salesiq.zohopublic.sa/widget?wc=e8345d69f3cb2a0945e374d8901771ae6b79f887aa0dd1ef960b4169df0f9cae'),
        'timeout' => (int) env('ZOHO_SALESIQ_TIMEOUT', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | متصفح توليد نماذج السير الذاتية
    |--------------------------------------------------------------------------
    | MaidCvPdfGenerator يلتقط صورة القالب عبر Chrome/Chromium بوضع headless.
    | بدون متصفح يسقط التوليد إلى SVG يحتوي HTML داخل <foreignObject>، وعندها
    | يعجز mPDF عن رسمه فيخرج ملف PDF مشوّه.
    |
    | كانت هذه القيم تُقرأ بـ env() مباشرة داخل الخدمة، وهو ما يُرجع null بعد
    | php artisan config:cache في الإنتاج — فيُتجاهل المسار المضبوط في .env.
    */
    'chrome' => [
        'path' => env('CV_PDF_BROWSER_PATH', ''),
        'viewport_width' => (int) env('CV_IMAGE_VIEWPORT_WIDTH', 794),
        'viewport_height' => (int) env('CV_IMAGE_VIEWPORT_HEIGHT', 1123),
    ],

];
