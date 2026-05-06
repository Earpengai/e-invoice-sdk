<?php

namespace CamInv\EInvoice\Tests\Unit\Support;

use CamInv\EInvoice\Support\Config;
use CamInv\EInvoice\Tests\TestCase;
use Illuminate\Contracts\Config\Repository;

class ConfigTest extends TestCase
{
    protected Repository $repository;

    protected Config $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make('config');
        $this->config = new Config($this->repository);
    }

    public function test_get_prepends_prefix(): void
    {
        $result = $this->config->get('client_id');

        $this->assertSame('test-client-id', $result);
    }

    public function test_get_with_default(): void
    {
        $result = $this->config->get('nonexistent_key', 'fallback');

        $this->assertSame('fallback', $result);
    }

    public function test_base_url(): void
    {
        $url = $this->config->baseUrl();

        $this->assertSame('https://api-sandbox.e-invoice.gov.kh', $url);
    }

    public function test_client_id_and_secret(): void
    {
        $this->assertSame('test-client-id', $this->config->clientId());
        $this->assertSame('test-client-secret', $this->config->clientSecret());
    }

    public function test_token_refresh_buffer_minutes(): void
    {
        $this->assertSame(5, $this->config->tokenRefreshBufferMinutes());
    }

    public function test_http_settings(): void
    {
        $this->assertSame(30, $this->config->httpTimeout());
        $this->assertSame(3, $this->config->httpRetries());
        $this->assertSame(100, $this->config->httpRetryDelay());
    }

    public function test_ubl_settings(): void
    {
        $namespaces = $this->config->ublNamespaces();
        $this->assertIsArray($namespaces);
        $this->assertArrayHasKey('cac', $namespaces);

        $this->assertSame('urn:cen.eu:en16931:2017', $this->config->ublCustomizationId());
        $this->assertSame('urn:fdc:peppol.eu:2017:poacc:billing:01:1.0', $this->config->ublProfileId());

        $taxCategories = $this->config->taxCategories();
        $this->assertIsArray($taxCategories);
        $this->assertArrayHasKey('S', $taxCategories);
        $this->assertSame(10.00, $taxCategories['S']['rate']);
    }

    public function test_default_currency(): void
    {
        $this->assertSame('KHR', $this->config->defaultCurrency());
    }
}
