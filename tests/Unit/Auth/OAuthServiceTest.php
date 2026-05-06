<?php

namespace CamInv\EInvoice\Tests\Unit\Auth;

use CamInv\EInvoice\Auth\OAuthService;
use CamInv\EInvoice\Client\CamInvClient;
use CamInv\EInvoice\Support\Config;
use CamInv\EInvoice\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class OAuthServiceTest extends TestCase
{
    protected OAuthService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $config = new Config($this->app->make('config'));
        $client = new CamInvClient('https://api-sandbox.e-invoice.gov.kh');

        $this->service = new OAuthService($client, $config);
    }

    public function test_configure_redirect_url(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/configure/configure-redirect-url' => Http::response(['status' => 'ok'], 200),
        ]);

        $this->service->configureRedirectUrl('https://example.com/callback');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api-sandbox.e-invoice.gov.kh/api/v1/configure/configure-redirect-url'
                && $request['redirect_url'] === 'https://example.com/callback';
        });
    }

    public function test_generate_connect_url(): void
    {
        $result = $this->service->generateConnectUrl('https://example.com/callback');

        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('state', $result);
        $this->assertStringContainsString('client_id=test-client-id', $result['url']);
        $this->assertStringContainsString('redirect_url=', $result['url']);
        $this->assertStringContainsString('state=', $result['url']);
        $this->assertSame(40, strlen($result['state']));
    }

    public function test_generate_connect_url_with_custom_state(): void
    {
        $result = $this->service->generateConnectUrl('https://example.com/callback', 'custom-state-123');

        $this->assertStringContainsString('state=custom-state-123', $result['url']);
        $this->assertSame('custom-state-123', $result['state']);
    }

    public function test_exchange_auth_token(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/auth/authorize/connect' => Http::response([
                'access_token' => 'access-123',
                'refresh_token' => 'refresh-456',
                'expires_in' => 3600,
                'endpoint_id' => 'KHUID00001234',
                'business_info' => ['company_name' => 'Test Co.'],
            ], 200),
        ]);

        $result = $this->service->exchangeAuthToken('auth-token-789');

        $this->assertSame('access-123', $result['access_token']);
        $this->assertSame('refresh-456', $result['refresh_token']);
        $this->assertSame('KHUID00001234', $result['endpoint_id']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api-sandbox.e-invoice.gov.kh/api/v1/auth/authorize/connect'
                && $request['authToken'] === 'auth-token-789';
        });
    }

    public function test_exchange_auth_token_without_access_token(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/auth/authorize/connect' => Http::response([
                'error' => 'invalid_grant',
            ], 400),
        ]);

        $this->expectException(\CamInv\EInvoice\Exceptions\AuthenticationException::class);
        $this->expectExceptionMessage('Invalid or expired authorization token');

        $this->service->exchangeAuthToken('bad-token');
    }

    public function test_refresh_access_token(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/auth/authorize/connect' => Http::response([
                'access_token' => 'new-access',
                'refresh_token' => 'new-refresh',
                'expires_in' => 3600,
            ], 200),
        ]);

        $result = $this->service->refreshAccessToken('old-refresh');

        $this->assertSame('new-access', $result['access_token']);
        $this->assertSame('new-refresh', $result['refresh_token']);
    }

    public function test_refresh_access_token_failure(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/auth/authorize/connect' => Http::response([
                'error' => 'invalid_grant',
            ], 400),
        ]);

        $this->expectException(\CamInv\EInvoice\Exceptions\AuthenticationException::class);
        $this->expectExceptionMessage('Access token has expired and refresh failed');

        $this->service->refreshAccessToken('bad-refresh');
    }
}
