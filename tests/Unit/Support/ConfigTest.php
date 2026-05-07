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
        $taxCategories = $this->config->taxCategories();
        $this->assertIsArray($taxCategories);
        $this->assertArrayHasKey('VAT', $taxCategories);
        $this->assertSame('Value Added Tax', $taxCategories['VAT']['name']);

        $taxSchemes = $this->config->taxSchemes();
        $this->assertIsArray($taxSchemes);
        $this->assertArrayHasKey('S', $taxSchemes);
        $this->assertSame('Standard', $taxSchemes['S']['name']);
    }

    public function test_default_currency(): void
    {
        $this->assertSame('KHR', $this->config->defaultCurrency());
    }
}
