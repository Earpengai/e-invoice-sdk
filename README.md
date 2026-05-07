# Cambodia E-Invoice SDK for Laravel

CamInv (Cambodia E-Invoicing) SDK for Laravel — OAuth 2.0 authentication, UBL 2.1 XML generation, document submission, webhook/polling event handling, and member management.

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Environment Variables](#environment-variables)
- [Quick Start](#quick-start)
  - [OAuth 2.0 Connection Flow](#oauth-20-connection-flow)
  - [UBL 2.1 XML Generation](#ubl-21-xml-generation)
  - [Credit/Debit Notes](#creditdebit-notes)
  - [Document Submission](#document-submission)
  - [Webhook Events](#webhook-events)
  - [Polling Events](#polling-events)
  - [Member Management](#member-management)
- [For-Merchant Pattern](#for-merchant-pattern)
- [Implementing TokenStore](#implementing-tokenstore)
- [Auto Token Refresh](#auto-token-refresh)
- [Facade API Reference](#facade-api-reference)
- [Enums](#enums)
- [Exceptions](#exceptions)
- [Tax Categories](#tax-categories)
- [Configuration Reference](#configuration-reference)
- [License](#license)

## Requirements

- PHP 8.1+
- Laravel 10.x, 11.x, 12.x, or 13.x
- ext-dom, ext-libxml

## Installation

```bash
composer require cambodia/e-invoice-sdk
```

Publish the configuration:

```bash
php artisan vendor:publish --tag=e-invoice-config
```

## Environment Variables

```env
CAMINV_ENVIRONMENT=sandbox
CAMINV_SANDBOX_URL=https://api-sandbox.e-invoice.gov.kh
CAMINV_PRODUCTION_URL=https://api.e-invoice.gov.kh
CAMINV_CLIENT_ID=your-client-id
CAMINV_CLIENT_SECRET=your-client-secret
CAMINV_WEBHOOK_URL=https://your-app.com/api/e-invoice/webhook
CAMINV_DEFAULT_CURRENCY=KHR
```

## Quick Start

### OAuth 2.0 Connection Flow

```php
use CamInv\EInvoice\Facades\CamInv;
use CamInv\EInvoice\Contracts\TokenStore;

// Step 1: Configure redirect URL (service-level, called once — uses Basic Auth)
CamInv::oauth()->configureRedirectUrl(['https://your-app.com/callback']);

// Step 2: Generate connect URL (with CSRF state parameter)
$result = CamInv::oauth()->generateConnectUrl('https://your-app.com/callback');
// Optionally pass your own state: ->generateConnectUrl($redirectUrl, $customState)
// $result['url']   → redirect user to this URL
// $result['state'] → store in session for callback validation

// In your callback controller:
public function callback(Request $request)
{
    // Validate the CSRF state parameter
    CamInv::oauth()->validateState($request->input('state'), session('caminv_state'));

    // Step 3: Exchange authorization token (uses Basic Auth)
    $tokens = CamInv::oauth()->exchangeAuthToken($request->input('authToken'));

    // Persist tokens using your TokenStore implementation
    $merchantId = $tokens['business_info']['endpoint_id'];
    app(TokenStore::class)->put($merchantId, $tokens);
    // tokens contain: access_token, refresh_token, expires_in, business_info
    //   business_info includes: endpoint_id, moc_id, company_name_en, company_name_kh,
    //   tin, city, phone_number, email
}

// Revoke a connected member (uses Basic Auth)
CamInv::oauth()->revokeConnectedMember($endpointId);
```

### UBL 2.1 XML Generation

```php
use CamInv\EInvoice\Facades\CamInv;

$xml = CamInv::ubl()->invoice()
    ->setId('INV-2026-00123')
    ->setIssueDate('2026-05-06')
    ->setDueDate('2026-06-03')
    ->setInvoiceTypeCode('380')
    ->setDocumentCurrencyCode('KHR')
    ->setBuyerReference('PO-2026-00456')
    ->setNote('Payment due within 30 days')
    ->setSupplier([
        'endpoint_id' => 'KHUID00001234',
        'party_name' => 'Your Company Ltd.',
        'postal_address' => [
            'street_name' => '123 Main Street',
            'city_name' => 'Phnom Penh',
            'country' => ['identification_code' => 'KH'],
        ],
        'party_tax_scheme' => [
            'company_id' => 'L001123456789',
            'tax_scheme_id' => 'VAT',
        ],
        'party_legal_entity' => [
            'registration_name' => 'Your Company Ltd.',
        ],
    ])
    ->setCustomer([
        'endpoint_id' => 'KHUID00005678',
        'party_name' => 'Customer Business Name',
        'postal_address' => [
            'street_name' => '456 Another Street',
            'city_name' => 'Siem Reap',
            'country' => ['identification_code' => 'KH'],
        ],
        'party_tax_scheme' => [
            'company_id' => 'L002987654321',
            'tax_scheme_id' => 'VAT',
        ],
    ])
    ->setTaxTotal([
        [
            'taxable_amount' => 1000.00,
            'tax_amount' => 100.00,
            'tax_category_id' => 'S',
            'percent' => 10.00,
            'tax_scheme_id' => 'VAT',
        ],
    ])
    ->setMonetaryTotal([
        'line_extension_amount' => 1000.00,
        'tax_exclusive_amount' => 1000.00,
        'tax_inclusive_amount' => 1100.00,
        'payable_amount' => 1100.00,
    ])
    ->setPaymentTerms([
        'note' => 'Payment due within 30 days',
        'settlement_discount_percent' => 2.0,
        'amount' => 22.0,
    ])
    ->addLine([
        'id' => '1',
        'quantity' => 10,
        'unit_code' => 'EA',
        'line_extension_amount' => 1000.00,
        'item' => [
            'name' => 'Product A',
            'description' => 'Sample product',
        ],
        'price' => [
            'price_amount' => 100.00,
        ],
        'tax_total' => [[
            'taxable_amount' => 1000.00,
            'tax_amount' => 100.00,
            'tax_category_id' => 'S',
            'percent' => 10.00,
        ]],
    ])
    ->build();

// Bulk add lines
$xml = CamInv::ubl()->invoice()
    ->setId('INV-2026-00123')
    // ...
    ->addLines([
        ['id' => '1', /* ... */],
        ['id' => '2', /* ... */],
    ])
    ->build();

// Advance Options: Allowances/charges and additional document references
$xml = CamInv::ubl()->invoice()
    ->setId('INV-2026-00124')
    // ...
    ->setAllowanceCharges([
        [
            'charge_indicator' => false,
            'amount' => 50.00,
            'allowance_charge_reason' => 'Loyalty discount',
        ],
    ])
    ->setAdditionalDocumentReferences([
        [
            'id' => 'ATT-001',
            'document_type' => 'Contract',
            'attachment' => [
                'file_path' => '/path/to/file.pdf',
                'mime_code' => 'application/pdf',
                'filename' => 'contract.pdf',
            ],
        ],
    ])
    ->build();
```

### Credit/Debit Notes

```php
// Credit Note (requires setOriginalInvoiceId and setNote)
$xml = CamInv::ubl()->creditNote()
    ->setOriginalInvoiceId('INV-2026-00123')
    ->setId('CN-2026-00045')
    ->setNote('Refund for overcharge')
    // ... same builder API as invoice
    ->build();

// Debit Note (requires setOriginalInvoiceId and setNote)
$xml = CamInv::ubl()->debitNote()
    ->setOriginalInvoiceId('INV-2026-00123')
    ->setId('DN-2026-00012')
    ->setNote('Additional charges for extended scope')
    // ... same builder API as invoice
    ->build();
```

### Document Submission

All document operations support automatic token resolution when using `forMerchant()` (see [For-Merchant Pattern](#for-merchant-pattern)) or you can pass an `$accessToken` explicitly.

```php
use CamInv\EInvoice\Facades\CamInv;
use CamInv\EInvoice\Enums\DocumentType;

// Submit a document (must specify DocumentType)
$result = CamInv::documents()->submit(DocumentType::INVOICE, $xml, $accessToken);
// Returns: { documents: [{ document_id, verification_link, ... }] }

// Send one or more documents to customer (batch)
$result = CamInv::documents()->send([$documentId], $accessToken);

// Accept one or more received documents (batch)
CamInv::documents()->accept([$documentId], $accessToken);

// Reject one or more documents with optional reason (batch)
CamInv::documents()->reject([$documentId], $accessToken, 'Incorrect pricing');

// Update document status (batch)
CamInv::documents()->updateStatus([$documentId], 'PAID', $accessToken);

// Fetch document XML/PDF from CamInv
$xml = CamInv::documents()->getXml($documentId, $accessToken);
$pdf = CamInv::documents()->getPdf($documentId, $accessToken);

// Get document detail/metadata
$detail = CamInv::documents()->getDetail($documentId, $accessToken);

// List your sent or received documents
$documents = CamInv::documents()->list($accessToken, 'send', 1, 20);
// $type: 'send' or 'receive', optional $documentType filter
```

### Webhook Events

```php
// In your Laravel controller
public function receive(Request $request)
{
    $event = CamInv::parseWebhook($request->all());

    if ($event->isDocumentDelivered()) {
        // Update document status to DELIVERED
    } elseif ($event->isDocumentReceived()) {
        // Create new received document record
    } elseif ($event->isStatusUpdated()) {
        // Update document status
        $newStatus = $event->status;
    } elseif ($event->isEntityRevoked()) {
        // Mark connection as revoked
    }
}

// Configure webhook URL for an endpoint (uses Basic Auth)
CamInv::webhooks()->configure($endpointId, $webhookUrl);

// Remove webhook configuration for an endpoint (uses Basic Auth)
CamInv::webhooks()->unset($endpointId);
```

### Polling Events

As an alternative to webhooks, you can poll for document events. This is useful when a public webhook URL is not available.

```php
use CamInv\EInvoice\Facades\CamInv;

// Poll for recent document events since a given timestamp
$lastSyncedAt = '2026-05-06T12:00:00Z'; // ISO 8601 format, or null for all
$events = CamInv::polling()->poll($lastSyncedAt, $accessToken);

foreach ($events as $event) {
    // Each event is a CamInv\EInvoice\Polling\PollEvent value object
    $event->documentId;    // string
    $event->updatedAt;     // string (ISO 8601)
    $event->type;          // "SEND" or "RECEIVE"
    $event->payload;       // array (full event data)

    if ($event->isSend()) {
        // Handle sent document event
    } elseif ($event->isReceive()) {
        // Handle received document event
    }
}

// With automatic token resolution
$events = CamInv::polling()->forMerchant($merchantId)->poll($lastSyncedAt);
```

### Member Management

```php
use CamInv\EInvoice\Facades\CamInv;

// Search members by company name, TIN, or endpoint ID
$members = CamInv::members()->list($accessToken, 'CompanyName', 20);

// Get detailed member information by endpoint ID
$member = CamInv::members()->get('KHUID00001234', $accessToken);

// Validate taxpayer information
$result = CamInv::members()->validateTaxpayer(
    tin: 'L001123456789',
    singleId: 'KHUID00001234',
    companyNameEn: 'Your Company Ltd.',
    companyNameKh: 'ក្រុមហ៊ុន',
    accessToken: $accessToken,
);
```

## For-Merchant Pattern

The `DocumentClient`, `MemberClient`, and `PollingClient` support a `forMerchant()` pattern for automatic token resolution. When you set a merchant context, the SDK automatically fetches and manages tokens via your `TokenStore` implementation — no need to pass `$accessToken` manually.

```php
use CamInv\EInvoice\Facades\CamInv;
use CamInv\EInvoice\Enums\DocumentType;

// Without forMerchant — pass token explicitly
CamInv::documents()->submit(DocumentType::INVOICE, $xml, $accessToken);

// With forMerchant — token resolved automatically from your TokenStore
CamInv::documents()->forMerchant($merchantId)->submit(DocumentType::INVOICE, $xml);

// All methods support this: submit, send, accept, reject, updateStatus,
// getXml, getPdf, getDetail, list

// Same for MemberClient and PollingClient
CamInv::members()->forMerchant($merchantId)->list(null, 'CompanyName');
CamInv::polling()->forMerchant($merchantId)->poll($lastSyncedAt);
```

## Implementing TokenStore

The SDK requires a `TokenStore` implementation for token persistence. Create one in your Laravel app:

```php
namespace App\Services\EInvoice;

use CamInv\EInvoice\Contracts\TokenStore;

class EloquentTokenStore implements TokenStore
{
    public function get(string $merchantId): ?array
    {
        $connection = \App\Models\EInvoice\Connection::where('merchant_id', $merchantId)->first();

        if (! $connection || ! $connection->access_token) {
            return null;
        }

        return [
            'access_token' => $connection->access_token,
            'refresh_token' => $connection->refresh_token,
            'expires_at' => $connection->token_expires_at->timestamp,
            'endpoint_id' => $connection->endpoint_id,
            'business_info' => $connection->business_info,
            'merchant_id' => $merchantId,
        ];
    }

    public function put(string $merchantId, array $tokenResponse): void
    {
        $expiresIn = $tokenResponse['expires_in'] ?? 3600;

        \App\Models\EInvoice\Connection::updateOrCreate(
            ['merchant_id' => $merchantId],
            [
                'access_token' => $tokenResponse['access_token'],
                'refresh_token' => $tokenResponse['refresh_token'],
                'token_expires_at' => now()->addSeconds($expiresIn),
                'endpoint_id' => $tokenResponse['business_info']['endpoint_id'] ?? null,
                'business_info' => $tokenResponse['business_info'] ?? null,
                'registration_status' => 'connected',
                'last_token_refresh_at' => now(),
            ]
        );
    }

    public function forget(string $merchantId): void
    {
        \App\Models\EInvoice\Connection::where('merchant_id', $merchantId)->update([
            'access_token' => null,
            'refresh_token' => null,
            'registration_status' => 'revoked',
        ]);
    }

    public function expiringWithin(int $seconds): array
    {
        return \App\Models\EInvoice\Connection::where('registration_status', 'connected')
            ->where('token_expires_at', '<', now()->addSeconds($seconds))
            ->get()
            ->map(fn ($c) => [
                'merchant_id' => $c->merchant_id,
                'access_token' => $c->access_token,
                'refresh_token' => $c->refresh_token,
                'expires_at' => $c->token_expires_at->timestamp,
            ])
            ->toArray();
    }
}
```

Then bind it in your `AppServiceProvider`:

```php
$this->app->bind(\CamInv\EInvoice\Contracts\TokenStore::class, \App\Services\EInvoice\EloquentTokenStore::class);
```

## Auto Token Refresh

The SDK supports automatic token refresh via the `HasTokenRefresh` trait. The `DocumentClient`, `MemberClient`, and `PollingClient` automatically refresh expired tokens when a 401 is received if the `forMerchant()` context is set.

Additionally, you can manage tokens explicitly:

```php
// Get a valid access token (auto-refreshes if expired)
$token = CamInv::token()->getValidAccessToken($merchantId);

// Manually refresh a merchant's access token
$result = CamInv::token()->refreshAccessToken($merchantId);

// Bulk refresh all tokens nearing expiry (useful for scheduled tasks)
$refreshed = CamInv::token()->refreshExpiringTokens();

// Check if a token is expired (with configurable buffer)
$isExpired = CamInv::token()->isTokenExpired($tokenData);
```

For scheduled token refresh, add to your `app/Console/Kernel.php`:

```php
$schedule->call(function () {
    CamInv::token()->refreshExpiringTokens();
})->everyFiveMinutes();
```

## Facade API Reference

```php
use CamInv\EInvoice\Facades\CamInv;
use CamInv\EInvoice\Enums\DocumentType;

// Client — direct HTTP access
CamInv::client()->withBearerToken($token)->get('/api/v1/...');
CamInv::client()->withBearerToken($token)->post('/api/v1/...');
CamInv::client()->withBearerToken($token)->put('/api/v1/...');
CamInv::client()->withBearerToken($token)->patch('/api/v1/...');
CamInv::client()->withBearerToken($token)->delete('/api/v1/...');
CamInv::client()->withBearerToken($token)->getRaw('/api/v1/...');
CamInv::client()->withBasicAuth()->post('/api/v1/...');

// OAuth (Basic Auth where noted)
CamInv::oauth()->configureRedirectUrl([$url]);
CamInv::oauth()->generateConnectUrl($redirectUrl, $state = null);
CamInv::oauth()->exchangeAuthToken($authToken);
CamInv::oauth()->refreshAccessToken($refreshToken);
CamInv::oauth()->revokeConnectedMember($endpointId);

// Token management
CamInv::token()->getValidAccessToken($merchantId);
CamInv::token()->isTokenExpired($token);
CamInv::token()->refreshAccessToken($merchantId);
CamInv::token()->refreshExpiringTokens();
CamInv::token()->calculateExpiresAt($expiresIn);

// Documents (all support forMerchant auto-token)
CamInv::documents()->submit(DocumentType::INVOICE, $xml, $token);
CamInv::documents()->send([$docId], $token);
CamInv::documents()->accept([$docId], $token);
CamInv::documents()->reject([$docId], $token, $reason = null);
CamInv::documents()->updateStatus([$docId], $status, $token);
CamInv::documents()->getXml($docId, $token);
CamInv::documents()->getPdf($docId, $token);
CamInv::documents()->getDetail($docId, $token);
CamInv::documents()->list($token, $type = 'send', $page = 1, $size = 20, $documentType = null);
CamInv::documents()->forMerchant($merchantId)->submit(DocumentType::INVOICE, $xml);

// UBL Builder
CamInv::ubl()->invoice($options)
    ->setId(...)
    ->setIssueDate(...)
    ->setDueDate(...)
    ->setNote(...)
    ->setBuyerReference(...)
    ->setInvoiceTypeCode(...)
    ->setDocumentCurrencyCode(...)
    ->setSupplier(...)
    ->setCustomer(...)
    ->setPaymentTerms(...)
    ->setAdditionalDocumentReferences(...)
    ->setAllowanceCharges(...)
    ->setTaxExchangeRate(...)
    ->setTaxTotal(...)
    ->setMonetaryTotal(...)
    ->addLine(...)
    ->addLines([...])
    ->build();

CamInv::ubl()->creditNote($options)->setOriginalInvoiceId(...)->build();
CamInv::ubl()->debitNote($options)->setOriginalInvoiceId(...)->build();

// Webhooks
CamInv::webhooks()->configure($endpointId, $webhookUrl);
CamInv::webhooks()->unset($endpointId);
CamInv::parseWebhook($payload);

// Members (supports forMerchant auto-token)
CamInv::members()->list($token, $keyword = '', $limit = 10);
CamInv::members()->get($endpointId, $token);
CamInv::members()->validateTaxpayer($tin, $singleId, $companyNameEn, $companyNameKh, $token);
CamInv::members()->forMerchant($merchantId)->list(null, 'CompanyName');

// Polling (supports forMerchant auto-token)
CamInv::polling()->poll($lastSyncedAt, $token);
CamInv::polling()->forMerchant($merchantId)->poll($lastSyncedAt);
```

> **Auto token resolution:** When `$token` is `null` and `forMerchant($merchantId)` is set on `DocumentClient`, `MemberClient`, or `PollingClient`, the `TokenManager` automatically resolves a valid access token from your `TokenStore` and refreshes it on 401. You can still pass an explicit `$accessToken` to bypass this behavior.

## Enums

```php
use CamInv\EInvoice\Enums\DocumentType;
use CamInv\EInvoice\Enums\DocumentStatus;
use CamInv\EInvoice\Enums\DocumentDirection;
use CamInv\EInvoice\Enums\WebhookEventType;
use CamInv\EInvoice\Enums\RegistrationStatus;
use CamInv\EInvoice\Enums\TaxCategory;

// DocumentType
DocumentType::INVOICE;             // 'INVOICE'
DocumentType::CREDIT_NOTE;         // 'CREDIT_NOTE'
DocumentType::DEBIT_NOTE;          // 'DEBIT_NOTE'
DocumentType::INVOICE->ublCode();  // '380'
DocumentType::CREDIT_NOTE->ublCode(); // '381'
DocumentType::DEBIT_NOTE->ublCode();  // '383'
DocumentType::INVOICE->xmlns();    // 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2'

// DocumentStatus
DocumentStatus::DRAFT;
DocumentStatus::SUBMITTING;
DocumentStatus::VALID;
DocumentStatus::DELIVERED;
DocumentStatus::ACKNOWLEDGED;
DocumentStatus::IN_PROCESS;
DocumentStatus::UNDER_QUERY;
DocumentStatus::CONDITIONALLY_ACCEPTED;
DocumentStatus::ACCEPTED;
DocumentStatus::REJECTED;
DocumentStatus::PAID;
DocumentStatus::ACCEPTED->isTerminal(); // true
DocumentStatus::REJECTED->isTerminal(); // true
DocumentStatus::PAID->isTerminal();     // true
DocumentStatus::ACCEPTED->color();      // 'green'

// DocumentDirection
DocumentDirection::SENT;
DocumentDirection::RECEIVED;

// WebhookEventType
WebhookEventType::DOCUMENT_DELIVERED;       // 'DOCUMENT.DELIVERED'
WebhookEventType::DOCUMENT_RECEIVED;        // 'DOCUMENT.RECEIVED'
WebhookEventType::DOCUMENT_STATUS_UPDATED;  // 'DOCUMENT.STATUS_UPDATED'
WebhookEventType::ENTITY_REVOKED;           // 'ENTITY.REVOKED'

// RegistrationStatus
RegistrationStatus::PENDING;     // 'pending'
RegistrationStatus::CONNECTED;   // 'connected'
RegistrationStatus::REVOKED;     // 'revoked'
RegistrationStatus::EXPIRED;     // 'expired'

// TaxCategory
TaxCategory::VAT;                     // 'VAT'
TaxCategory::SPECIFIC_TAX;            // 'SP'
TaxCategory::PUBLIC_LIGHTING_TAX;     // 'PLT'
TaxCategory::ACCOMMODATION_TAX;       // 'AT'
TaxCategory::VAT->defaultRate();      // 10.0
TaxCategory::SPECIFIC_TAX->defaultRate(); // 0.0
```

## Exceptions

All exceptions extend `CamInv\EInvoice\Exceptions\CamInvException` which provides `getStatusCode()` and `getResponseBody()` methods.

| Exception | Description |
|---|---|
| `AuthenticationException` | Invalid credentials, expired/invalid token, CSRF state mismatch, invalid auth token, revoke failure |
| `ConnectionException` | HTTP timeout, network error, SSL error |
| `TokenExpiredException` | Token expired and cannot be refreshed, no token stored |
| `ValidationException` | Invalid UBL XML, missing required fields, invalid document status, submission failure |

```php
try {
    CamInv::documents()->submit(DocumentType::INVOICE, $xml, $token);
} catch (\CamInv\EInvoice\Exceptions\AuthenticationException $e) {
    // Handle auth failure (401)
} catch (\CamInv\EInvoice\Exceptions\ValidationException $e) {
    // Handle validation error (422)
} catch (\CamInv\EInvoice\Exceptions\ConnectionException $e) {
    // Handle network error
} catch (\CamInv\EInvoice\Exceptions\CamInvException $e) {
    // Generic fallback
    $statusCode = $e->getStatusCode();
    $body = $e->getResponseBody();
}
```

## Tax Categories

Configurable via `config/e-invoice.php` → `ubl.tax_categories`.

| Constant | Enum Value | Label | Default Rate |
|---|---|---|---|
| `TaxCategory::VAT` | `VAT` | Value Added Tax | 10% |
| `TaxCategory::SPECIFIC_TAX` | `SP` | Specific Tax | 0% |
| `TaxCategory::PUBLIC_LIGHTING_TAX` | `PLT` | Public Lighting Tax | 0% |
| `TaxCategory::ACCOMMODATION_TAX` | `AT` | Accommodation Tax | 0% |

Tax schemes are configured separately at `ubl.tax_schemes`:

| ID | Name |
|---|---|
| `S` | Standard |
| `Z` | Zero |

## Configuration Reference

| Config Key | Env Variable | Default | Description |
|---|---|---|---|
| `default_environment` | `CAMINV_ENVIRONMENT` | `sandbox` | `sandbox` or `production` |
| `environments.sandbox.base_url` | `CAMINV_SANDBOX_URL` | `https://api-sandbox.e-invoice.gov.kh` | Sandbox API URL |
| `environments.production.base_url` | `CAMINV_PRODUCTION_URL` | `https://api.e-invoice.gov.kh` | Production API URL |
| `client_id` | `CAMINV_CLIENT_ID` | — | OAuth client ID from CamInv |
| `client_secret` | `CAMINV_CLIENT_SECRET` | — | OAuth client secret from CamInv |
| `webhook_url` | `CAMINV_WEBHOOK_URL` | `/api/e-invoice/webhook` | Default webhook URL |
| `token.refresh_buffer_minutes` | — | `5` | Minutes before expiry to proactively refresh |
| `http.timeout` | — | `30` | HTTP timeout in seconds |
| `http.retries` | — | `3` | Number of retry attempts |
| `http.retry_delay` | — | `100` | Delay between retries (ms) |
| `ubl.tax_categories` | — | `VAT`, `SP`, `PLT`, `AT` | Tax category definitions |
| `ubl.tax_schemes` | — | `S` (Standard), `Z` (Zero) | Tax scheme definitions |
| `ubl.default_currency` | `CAMINV_DEFAULT_CURRENCY` | `KHR` | Default currency code |

## License

MIT
