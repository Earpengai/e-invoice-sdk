<?php

namespace CamInv\EInvoice\Support;

use Illuminate\Contracts\Config\Repository;

class Config
{
    public function __construct(
        private Repository $config,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config->get("e-invoice.{$key}", $default);
    }

    public function baseUrl(): string
    {
        $env = $this->get('default_environment', 'sandbox');

        return $this->get("environments.{$env}.base_url");
    }

    public function clientId(): string
    {
        return (string) $this->get('client_id', '');
    }

    public function clientSecret(): string
    {
        return (string) $this->get('client_secret', '');
    }

    public function tokenRefreshBufferMinutes(): int
    {
        return (int) $this->get('token.refresh_buffer_minutes', 5);
    }

    public function httpTimeout(): int
    {
        return (int) $this->get('http.timeout', 30);
    }

    public function httpRetries(): int
    {
        return (int) $this->get('http.retries', 3);
    }

    public function httpRetryDelay(): int
    {
        return (int) $this->get('http.retry_delay', 100);
    }

    public function taxCategories(): array
    {
        return (array) $this->get('ubl.tax_categories', []);
    }

    public function taxSchemes(): array
    {
        return (array) $this->get('ubl.tax_schemes', []);
    }

    public function defaultCurrency(): string
    {
        return (string) $this->get('ubl.default_currency', 'KHR');
    }
}
