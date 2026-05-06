# Cambodia E-Invoice SDK for Laravel

CamInv (Cambodia E-Invoicing) SDK for Laravel — OAuth 2.0 authentication, UBL 2.1 XML generation, document submission, webhook handling, and member management.

## Requirements

- PHP 8.1+
- Laravel 10.x or 11.x
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

// Step 1: Configure redirect URL (service-level, called once)
CamInv::oauth()->configureRedirectUrl('https://your-app.com/callback');

// Step 2: Generate connect URL (with CSRF state parameter)
$result = CamInv::oauth()->generateConnectUrl('https://your-app.com/callback');
// $result['url']   → redirect user to this URL
// $result['state'] → store in session for callback validation

// Step 3: Exchange authorization token
$tokens = CamInv::oauth()->exchangeAuthToken($authToken);
// Returns: access_token, refresh_token, expires_in, endpoint_id, business_info
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

// $xml is a valid UBL 2.1 Invoice XML string
```

### Credit/Debit Notes

```php
// Credit Note
$xml = CamInv::ubl()->creditNote()
    ->setOriginalInvoiceId('INV-2026-00123')
    ->setId('CN-2026-00045')
    // ... same builder API as invoice
    ->build();

// Debit Note
$xml = CamInv::ubl()->debitNote()
    ->setOriginalInvoiceId('INV-2026-00123')
    ->setId('DN-2026-00012')
    // ... same builder API as invoice
    ->build();
```

### Document Submission

```php
// Submit invoice
$result = CamInv::documents()->submit($xml, $accessToken);
// Returns: { documents: [{ document_id, verification_link, ... }] }

// Send to customer
$result = CamInv::documents()->send($documentId, $customerEndpointId, $accessToken);

// Accept received document
CamInv::documents()->accept($documentId, $accessToken);

// Reject with reason
CamInv::documents()->reject($documentId, $accessToken, 'Incorrect pricing');

// Fetch document XML/PDF from CamInv
$xml = CamInv::documents()->getXml($documentId, $accessToken);
$pdf = CamInv::documents()->getPdf($documentId, $accessToken);
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
    } elseif ($event->isEntityRevoked()) {
        // Mark connection as revoked
    }
}
```

### Member Management

```php
// List members
$members = CamInv::members()->list($accessToken);

// Validate taxpayer
$result = CamInv::members()->validateTaxpayer('L001123456789', $accessToken);
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
                'endpoint_id' => $tokenResponse['endpoint_id'] ?? null,
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

## Facade API Reference

```php
use CamInv\EInvoice\Facades\CamInv;

// Client — direct HTTP access
CamInv::client()->withBearerToken($token)->get('/api/v1/...');
CamInv::client()->withBasicAuth()->post('/api/v1/...');

// OAuth
CamInv::oauth()->configureRedirectUrl($url);
CamInv::oauth()->generateConnectUrl($redirectUrl, $state);
CamInv::oauth()->exchangeAuthToken($authToken);
CamInv::oauth()->refreshAccessToken($refreshToken);

// Token management
CamInv::token()->getValidAccessToken($merchantId);
CamInv::token()->isTokenExpired($token);
CamInv::token()->refreshAccessToken($merchantId);
CamInv::token()->refreshExpiringTokens();

// Documents
CamInv::documents()->submit($xml, $token);
CamInv::documents()->send($documentId, $endpointId, $token);
CamInv::documents()->accept($documentId, $token);
CamInv::documents()->reject($documentId, $token, $reason);
CamInv::documents()->getXml($documentId, $token);
CamInv::documents()->getPdf($documentId, $token);
CamInv::documents()->getDetail($documentId, $token);

// UBL Builder
CamInv::ubl()->invoice($options)->setId(...)->build();
CamInv::ubl()->creditNote($options)->setOriginalInvoiceId(...)->build();
CamInv::ubl()->debitNote($options)->setOriginalInvoiceId(...)->build();

// Webhooks
CamInv::webhooks()->configure($endpointId, $webhookUrl);
CamInv::parseWebhook($payload);

// Members
CamInv::members()->list($token);
CamInv::members()->validateTaxpayer($tin, $token);
```

## Enums

```php
use CamInv\EInvoice\Enums\DocumentType;
use CamInv\EInvoice\Enums\DocumentStatus;
use CamInv\EInvoice\Enums\DocumentDirection;
use CamInv\EInvoice\Enums\WebhookEventType;
use CamInv\EInvoice\Enums\RegistrationStatus;
use CamInv\EInvoice\Enums\TaxCategory;

DocumentType::INVOICE->ublCode();       // '380'
DocumentType::CREDIT_NOTE->ublCode();   // '381'
DocumentStatus::ACCEPTED->isTerminal(); // true
DocumentStatus::ACCEPTED->color();      // 'green'
TaxCategory::STANDARD->defaultRate();   // 10.0
```

## Tax Categories

| ID | Label | Default Rate |
|----|-------|--------------|
| `S` | Standard Rate (VAT) | 10% |
| `Z` | Zero Rated | 0% |
| `E` | Exempt | 0% |

Configurable via `config/e-invoice.php` → `ubl.tax_categories`.

## License

MIT
