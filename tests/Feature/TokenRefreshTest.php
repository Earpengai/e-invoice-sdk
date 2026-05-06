<?php

namespace CamInv\EInvoice\Tests\Feature;

use CamInv\EInvoice\Contracts\TokenStore;
use CamInv\EInvoice\Token\TokenManager;
use CamInv\EInvoice\Tests\TestCase;
use Mockery;

class TokenRefreshTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_full_token_refresh_cycle(): void
    {
        $store = Mockery::mock(TokenStore::class);
        $this->app->instance(TokenStore::class, $store);

        $this->assertTrue($this->app->bound(TokenManager::class));

        $manager = $this->app->make(TokenManager::class);

        $this->assertTrue($manager->isTokenExpired([
            'access_token' => 'old-access',
            'expires_at' => time() - 1000,
        ]));

        $expiresAt = $manager->calculateExpiresAt(3600);

        $this->assertGreaterThan(time(), $expiresAt);
    }

    public function test_batch_refresh_handles_errors(): void
    {
        $store = Mockery::mock(TokenStore::class);
        $this->app->instance(TokenStore::class, $store);

        $expiring = [
            ['merchant_id' => 'merchant-1', 'access_token' => 't1', 'refresh_token' => 'r1', 'expires_at' => time() + 60],
            ['merchant_id' => 'merchant-2', 'access_token' => 't2', 'refresh_token' => 'r2', 'expires_at' => time() + 60],
        ];

        $store->shouldReceive('expiringWithin')->with(300)->once()->andReturn($expiring);

        $store->shouldReceive('get')->with('merchant-1')->once()->andReturn($expiring[0]);
        $store->shouldReceive('get')->with('merchant-2')->once()->andReturn($expiring[1]);

        $oauth = Mockery::mock(\CamInv\EInvoice\Auth\OAuthService::class);
        $this->app->instance(\CamInv\EInvoice\Auth\OAuthService::class, $oauth);

        $oauth->shouldReceive('refreshAccessToken')->with('r1')->once()
            ->andReturn(['access_token' => 'new1']);

        $oauth->shouldReceive('refreshAccessToken')->with('r2')->once()
            ->andThrow(new \CamInv\EInvoice\Exceptions\AuthenticationException('Failed'));

        $store->shouldReceive('put')->with('merchant-1', ['access_token' => 'new1'])->once();

        $config = new \CamInv\EInvoice\Support\Config($this->app->make('config'));
        $manager = new TokenManager($store, $oauth, $config);

        $results = $manager->refreshExpiringTokens();

        $this->assertArrayHasKey('merchant-1', $results);
        $this->assertSame(['access_token' => 'new1'], $results['merchant-1']);

        $this->assertArrayHasKey('merchant-2', $results);
        $this->assertArrayHasKey('error', $results['merchant-2']);
    }
}
