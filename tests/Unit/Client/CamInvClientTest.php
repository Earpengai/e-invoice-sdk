<?php

namespace CamInv\EInvoice\Tests\Unit\Client;

use CamInv\EInvoice\Client\CamInvClient;
use CamInv\EInvoice\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class CamInvClientTest extends TestCase
{
    protected CamInvClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new CamInvClient('https://api-sandbox.e-invoice.gov.kh');
    }

    public function test_get_with_basic_auth(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/member' => Http::response(['members' => []], 200),
        ]);

        $response = $this->client->withBasicAuth()->get('/api/v1/member');

        $this->assertSame(['members' => []], $response);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization')
                && str_starts_with($request->header('Authorization')[0], 'Basic ');
        });
    }

    public function test_post_with_bearer_token(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/document' => Http::response(['status' => 'ok'], 200),
        ]);

        $response = $this->client->withBearerToken('test-token')->post('/api/v1/document', ['data' => 'value']);

        $this->assertSame(['status' => 'ok'], $response);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization')
                && $request->header('Authorization')[0] === 'Bearer test-token';
        });
    }

    public function test_get_with_query_params(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/document/*' => Http::response(['id' => '123'], 200),
        ]);

        $response = $this->client->withBearerToken('token')->get('/api/v1/document/uuid-123');

        $this->assertSame(['id' => '123'], $response);
    }

    public function test_error_response_throws_exception(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/*' => Http::response([
                'error' => 'invalid_request',
                'message' => 'Invalid request parameters',
            ], 422),
        ]);

        try {
            $this->client->withBearerToken('token')->post('/api/v1/document', []);
            $this->fail('Expected CamInvException was not thrown');
        } catch (\CamInv\EInvoice\Exceptions\CamInvException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertNotNull($e->getResponseBody());
            $this->assertStringContainsString('Invalid request parameters', $e->getMessage());
        }
    }

    public function test_get_raw_returns_string(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/document/*/xml' => Http::response('<Invoice>...</Invoice>', 200),
        ]);

        $xml = $this->client->withBearerToken('token')->getRaw('/api/v1/document/uuid-123/xml');

        $this->assertSame('<Invoice>...</Invoice>', $xml);
    }

    public function test_fluent_auth_switching(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/*' => Http::response(['ok' => true], 200),
        ]);

        $this->client->withBasicAuth()->post('/api/v1/configure/configure-redirect-url', ['redirect_url' => 'https://example.com']);

        Http::assertSent(function ($request) {
            return str_starts_with($request->header('Authorization')[0], 'Basic ');
        });

        $this->client->withBearerToken('bearer-123')->get('/api/v1/member');

        Http::assertSent(function ($request) {
            return $request->header('Authorization')[0] === 'Bearer bearer-123';
        });
    }

    public function test_retry_on_server_error(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/*' => Http::sequence()
                ->push(['error' => 'server error'], 500)
                ->push(['ok' => true], 200),
        ]);

        $response = $this->client->withBearerToken('token')->get('/api/v1/document/uuid-123');

        $this->assertSame(['ok' => true], $response);
    }
}
