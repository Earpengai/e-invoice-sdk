<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Environment
    |--------------------------------------------------------------------------
    |
    | Options: 'sandbox' or 'production'
    |
    */

    'default_environment' => env('CAMINV_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | API Base URLs
    |--------------------------------------------------------------------------
    */

    'environments' => [
        'sandbox' => [
            'base_url' => env('CAMINV_SANDBOX_URL', 'https://api-sandbox.e-invoice.gov.kh'),
        ],
        'production' => [
            'base_url' => env('CAMINV_PRODUCTION_URL', 'https://api.e-invoice.gov.kh'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Provider Credentials
    |--------------------------------------------------------------------------
    |
    | These are your client ID and client secret obtained from CamInv.
    | Used for service-level Basic Auth when configuring redirect URLs,
    | webhooks, and exchanging authorization codes.
    |
    */

    'client_id' => env('CAMINV_CLIENT_ID'),

    'client_secret' => env('CAMINV_CLIENT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Webhook
    |--------------------------------------------------------------------------
    */

    'webhook_url' => env('CAMINV_WEBHOOK_URL', '/api/e-invoice/webhook'),

    /*
    |--------------------------------------------------------------------------
    | Token Management
    |--------------------------------------------------------------------------
    */

    'token' => [
        'refresh_buffer_minutes' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Settings
    |--------------------------------------------------------------------------
    */

    'http' => [
        'timeout' => 30,
        'retries' => 3,
        'retry_delay' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | UBL (Universal Business Language) Settings
    |--------------------------------------------------------------------------
    */

    'ubl' => [
        'tax_categories' => [
            'VAT' => ['name' => 'Value Added Tax'],
            'SP' => ['name' => 'Specific Tax'],
            'PLT' => ['name' => 'Public Lighting Tax'],
            'AT' => ['name' => 'Accommodation Tax'],
        ],
        'tax_schemes' => [
            'S' => ['name' => 'Standard'],
            'Z' => ['name' => 'Zero'],
        ],
        'default_currency' => env('CAMINV_DEFAULT_CURRENCY', 'KHR'),
    ],

];
