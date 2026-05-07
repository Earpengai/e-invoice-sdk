<?php

namespace CamInv\EInvoice\Tests\Unit\Token;

use CamInv\EInvoice\Auth\OAuthService;
use CamInv\EInvoice\Contracts\TokenStore;
use CamInv\EInvoice\Support\Config;
use CamInv\EInvoice\Token\TokenManager;
use CamInv\EInvoice\Tests\TestCase;
use Mockery;

class TokenManagerTest extends TestCase
{
    protected TokenStore $store;

    protected OAuthService $authService;

    protected Config $config;

    protected TokenManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Mockery::mock(TokenStore::class);
        $this->authService = Mockery::mock(OAuthService::class);
        $this->config = new Config($this->app->make('config'));

        $this->manager = new TokenManager($this->store, $this->authService, $this->config);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_valid_access_token_returns_stored_token(): void
    {
        $token = [
            'access_token' => 'valid-token',
            'refresh_token' => 'refresh-token',
            'expires_at' => time() + 3600,
        ];

        $this->store->shouldReceive('get')->with('merchant-1')->once()->andReturn($token);

        $result = $this->manager->getValidAccessToken('merchant-1');

        $this->assertSame('valid-token', $result);
    }

    public function test_get_valid_access_token_auto_refreshes_when_expired(): void
    {
        $expiredToken = [
            'access_token' => 'expired-token',
            'refresh_token' => 'refresh-token',
            'expires_at' => time() - 100,
        ];

        $newToken = [
            'access_token' => 'new-valid-token',
            'refresh_token' => 'new-refresh-token',
            'expires_in' => 3600,
        ];

        $this->store->shouldReceive('get')->with('merchant-1')->twice()->andReturn($expiredToken);
        $this->authService->shouldReceive('refreshAccessToken')->with('refresh-token')->once()->andReturn($newToken);
        $this->store->shouldReceive('put')->with('merchant-1', $newToken)->once();

        $result = $this->manager->getValidAccessToken('merchant-1');

        $this->assertSame('new-valid-token', $result);
    }

    public function test_get_valid_access_token_throws_when_no_token(): void
    {
        $this->store->shouldReceive('get')->with('merchant-1')->once()->andReturn(null);

        $this->expectException(\CamInv\EInvoice\Exceptions\TokenExpiredException::class);
        $this->expectExceptionMessage('No tokens stored');

        $this->manager->getValidAccessToken('merchant-1');
    }

    public function test_is_token_expired_with_buffer(): void
    {
        $tokenNearExpiry = [
            'access_token' => 'token',
            'expires_at' => time() + 120,
        ];

        $this->assertTrue($this->manager->isTokenExpired($tokenNearExpiry));

        $tokenFarFromExpiry = [
            'access_token' => 'token',
            'expires_at' => time() + 3600,
        ];

        $this->assertFalse($this->manager->isTokenExpired($tokenFarFromExpiry));
    }

    public function test_is_token_expired_without_expires_at(): void
    {
        $this->assertTrue($this->manager->isTokenExpired(['access_token' => 'token']));
    }

    public function test_refresh_access_token_success(): void
    {
        $storedToken = [
            'access_token' => 'old-token',
            'refresh_token' => 'old-refresh',
            'expires_at' => time() - 100,
        ];

        $newToken = [
            'access_token' => 'new-token',
            'refresh_token' => 'new-refresh',
            'expires_in' => 3600,
        ];

        $this->store->shouldReceive('get')->with('merchant-1')->once()->andReturn($storedToken);
        $this->authService->shouldReceive('refreshAccessToken')->with('old-refresh')->once()->andReturn($newToken);
        $this->store->shouldReceive('put')->with('merchant-1', $newToken)->once();

        $result = $this->manager->refreshAccessToken('merchant-1');

        $this->assertSame($newToken, $result);
    }

    public function test_refresh_access_token_fails_when_no_stored_token(): void
    {
        $this->store->shouldReceive('get')->with('merchant-1')->once()->andReturn(null);

        $this->expectException(\CamInv\EInvoice\Exceptions\TokenExpiredException::class);
        $this->expectExceptionMessage('expired and could not be refreshed');

        $this->manager->refreshAccessToken('merchant-1');
    }

    public function test_refresh_expiring_tokens(): void
    {
        $expiring = [
            ['merchant_id' => 'merchant-1', 'access_token' => 't1', 'refresh_token' => 'r1', 'expires_at' => time() + 60],
            ['merchant_id' => 'merchant-2', 'access_token' => 't2', 'refresh_token' => 'r2', 'expires_at' => time() + 60],
        ];

        $this->store->shouldReceive('expiringWithin')->with(300)->once()->andReturn($expiring);

        $this->store->shouldReceive('get')->with('merchant-1')->once()->andReturn($expiring[0]);
        $this->authService->shouldReceive('refreshAccessToken')->with('r1')->once()->andReturn(['access_token' => 'new1']);
        $this->store->shouldReceive('put')->with('merchant-1', ['access_token' => 'new1', 'refresh_token' => 'r1'])->once();

        $this->store->shouldReceive('get')->with('merchant-2')->once()->andReturn($expiring[1]);
        $this->authService->shouldReceive('refreshAccessToken')->with('r2')->once()->andReturn(['access_token' => 'new2']);
        $this->store->shouldReceive('put')->with('merchant-2', ['access_token' => 'new2', 'refresh_token' => 'r2'])->once();

        $results = $this->manager->refreshExpiringTokens();

        $this->assertArrayHasKey('merchant-1', $results);
        $this->assertArrayHasKey('merchant-2', $results);
    }

    public function test_calculate_expires_at(): void
    {
        $result = $this->manager->calculateExpiresAt(3600);

        $this->assertGreaterThan(time(), $result);
        $this->assertLessThanOrEqual(time() + 3601, $result);
    }
}
