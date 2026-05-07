<?php

namespace CamInv\EInvoice\Tests\Unit\Polling;

use CamInv\EInvoice\Client\CamInvClient;
use CamInv\EInvoice\Contracts\TokenStore;
use CamInv\EInvoice\Polling\PollingClient;
use CamInv\EInvoice\Support\Config;
use CamInv\EInvoice\Token\TokenManager;
use CamInv\EInvoice\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Mockery;

class PollingClientTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function createClient(): PollingClient
    {
        $store = Mockery::mock(TokenStore::class);
        $config = $this->app->make(Config::class);
        $tokenManager = new TokenManager($store, Mockery::mock(\CamInv\EInvoice\Auth\OAuthService::class), $config);

        return new PollingClient(
            new CamInvClient('https://api-sandbox.e-invoice.gov.kh'),
            $tokenManager,
        );
    }

    public function test_poll_with_last_synced_at(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/document/poll*' => Http::response([
                [
                    'document_id' => 'uuid-123',
                    'updated_at' => '2024-06-01T01:30:00',
                    'type' => 'SEND',
                ],
                [
                    'document_id' => 'uuid-456',
                    'updated_at' => '2024-06-01T02:00:00',
                    'type' => 'RECEIVE',
                ],
            ], 200),
        ]);

        $client = $this->createClient();
        $results = $client->poll('2024-06-01T01:00:00', 'test-access-token');

        $this->assertCount(2, $results);
        $this->assertSame('uuid-123', $results[0]->documentId);
        $this->assertSame('SEND', $results[0]->type);
        $this->assertTrue($results[0]->isSend());
        $this->assertSame('uuid-456', $results[1]->documentId);
        $this->assertSame('RECEIVE', $results[1]->type);
        $this->assertTrue($results[1]->isReceive());

        Http::assertSent(function ($request) {
            $parsedUrl = parse_url($request->url());

            return $request->method() === 'GET'
                && str_starts_with($parsedUrl['path'], '/api/v1/document/poll')
                && str_contains($request->url(), 'last_synced_at=')
                && $request->hasHeader('Authorization', 'Bearer test-access-token');
        });
    }

    public function test_poll_without_last_synced_at(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/document/poll' => Http::response([
                [
                    'document_id' => 'uuid-789',
                    'updated_at' => '2024-07-01T00:00:00',
                    'type' => 'SEND',
                ],
            ], 200),
        ]);

        $client = $this->createClient();
        $results = $client->poll(null, 'test-access-token');

        $this->assertCount(1, $results);
        $this->assertSame('uuid-789', $results[0]->documentId);

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'https://api-sandbox.e-invoice.gov.kh/api/v1/document/poll';
        });
    }

    public function test_poll_returns_empty_array_when_no_updates(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/document/poll*' => Http::response([], 200),
        ]);

        $client = $this->createClient();
        $results = $client->poll('2024-06-01T01:00:00', 'test-access-token');

        $this->assertSame([], $results);
    }
}
