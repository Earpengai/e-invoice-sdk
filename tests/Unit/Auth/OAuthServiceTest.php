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

        $this->service->configureRedirectUrl(['https://example.com/callback']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api-sandbox.e-invoice.gov.kh/api/v1/configure/configure-redirect-url'
                && $request['white_list_redirect_urls'] === ['https://example.com/callback'];
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
                'business_info' => [
                    'endpoint_id' => 'KHUID00001234',
                    'company_name_en' => 'Test Co.',
                    'tin' => 'L001123456789',
                ],
            ], 200),
        ]);

        $result = $this->service->exchangeAuthToken('auth-token-789');

        $this->assertSame('access-123', $result['access_token']);
        $this->assertSame('refresh-456', $result['refresh_token']);
        $this->assertSame('KHUID00001234', $result['business_info']['endpoint_id']);
        $this->assertSame('Test Co.', $result['business_info']['company_name_en']);

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
            'api-sandbox.e-invoice.gov.kh/api/v1/auth/token' => Http::response([
                'access_token' => 'new-access',
                'expireIn' => '15m',
                'expire_at' => '2026-05-07T01:37:57.510Z',
                'expired_at' => '2026-05-07T01:37:57.510Z',
            ], 200),
        ]);

        $result = $this->service->refreshAccessToken('old-refresh');

        $this->assertSame('new-access', $result['access_token']);
        $this->assertSame('2026-05-07T01:37:57.510Z', $result['expire_at']);
    }

    public function test_refresh_access_token_failure(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/auth/token' => Http::response([
                'error' => 'invalid_grant',
            ], 400),
        ]);

        $this->expectException(\CamInv\EInvoice\Exceptions\AuthenticationException::class);
        $this->expectExceptionMessage('Access token has expired and refresh failed');

        $this->service->refreshAccessToken('bad-refresh');
    }

    public function test_revoke_connected_member(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/auth/revoke' => Http::response([
                'message' => 'Access revoked successfully',
            ], 200),
        ]);

        $result = $this->service->revokeConnectedMember('KHUID00001234');

        $this->assertSame('Access revoked successfully', $result['message']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api-sandbox.e-invoice.gov.kh/api/v1/auth/revoke'
                && $request['endpoint_id'] === 'KHUID00001234';
        });
    }

    public function test_revoke_connected_member_failure(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/auth/revoke' => Http::response([
                'error' => 'not_found',
            ], 400),
        ]);

        $this->expectException(\CamInv\EInvoice\Exceptions\AuthenticationException::class);
        $this->expectExceptionMessage('Failed to revoke connected member');

        $this->service->revokeConnectedMember('invalid-id');
    }
}
