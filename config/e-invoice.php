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
        'namespaces' => [
            '' => 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
            'cac' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2',
            'cbc' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
        ],
        'customization_id' => 'urn:cen.eu:en16931:2017',
        'profile_id' => 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
        'tax_categories' => [
            'S' => ['name' => 'Standard Rate', 'rate' => 10.00],
            'Z' => ['name' => 'Zero Rated', 'rate' => 0.00],
            'E' => ['name' => 'Exempt', 'rate' => 0.00],
        ],
        'default_currency' => env('CAMINV_DEFAULT_CURRENCY', 'KHR'),
    ],

];
