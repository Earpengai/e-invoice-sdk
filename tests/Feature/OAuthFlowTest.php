<?php

namespace CamInv\EInvoice\Tests\Feature;

use CamInv\EInvoice\Auth\OAuthService;
use CamInv\EInvoice\Contracts\TokenStore;
use CamInv\EInvoice\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Mockery;

class OAuthFlowTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_full_oauth_flow(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/configure/configure-redirect-url' => Http::response(['status' => 'ok'], 200),
            'api-sandbox.e-invoice.gov.kh/api/v1/auth/authorize/connect' => Http::response([
                'access_token' => 'access-xyz',
                'refresh_token' => 'refresh-xyz',
                'expires_in' => 3600,
                'endpoint_id' => 'KHUID00001234',
                'business_info' => ['company_name' => 'Test Co.', 'tin' => 'L001123456789'],
            ], 200),
        ]);

        $store = Mockery::mock(TokenStore::class);
        $this->app->instance(TokenStore::class, $store);

        $oauthService = $this->app->make(OAuthService::class);

        $oauthService->configureRedirectUrl('https://test-app.com/callback');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'configure-redirect-url')
                && $request['redirect_url'] === 'https://test-app.com/callback';
        });

        $connect = $oauthService->generateConnectUrl('https://test-app.com/callback');

        $this->assertNotEmpty($connect['url']);
        $this->assertNotEmpty($connect['state']);
        $this->assertStringContainsString('client_id=test-client-id', $connect['url']);
        $this->assertStringContainsString('state=' . $connect['state'], $connect['url']);

        $tokens = $oauthService->exchangeAuthToken('sample-auth-token');

        $this->assertSame('access-xyz', $tokens['access_token']);
        $this->assertSame('refresh-xyz', $tokens['refresh_token']);
        $this->assertSame('KHUID00001234', $tokens['endpoint_id']);
        $this->assertSame('Test Co.', $tokens['business_info']['company_name']);
    }

    public function test_state_validation(): void
    {
        $oauthService = $this->app->make(OAuthService::class);

        $connect = $oauthService->generateConnectUrl('https://test-app.com/callback');

        $this->assertTrue($oauthService->validateState($connect['state'], $connect['state']));
        $this->assertFalse($oauthService->validateState($connect['state'], 'wrong-state'));
    }
}
