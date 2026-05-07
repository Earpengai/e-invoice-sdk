<?php

namespace CamInv\EInvoice\Tests\Unit\Member;

use CamInv\EInvoice\Client\CamInvClient;
use CamInv\EInvoice\Member\MemberClient;
use CamInv\EInvoice\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class MemberClientTest extends TestCase
{
    protected MemberClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new MemberClient(
            new CamInvClient('https://api-sandbox.e-invoice.gov.kh')
        );
    }

    public function test_list_members(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/business*' => Http::response([
                'members' => [
                    ['endpoint_id' => 'KHUID00001234', 'name' => 'Test Co.'],
                ],
            ], 200),
        ]);

        $result = $this->client->list('token-123', 'Test', 10);

        $this->assertCount(1, $result['members']);
        $this->assertSame('KHUID00001234', $result['members'][0]['endpoint_id']);

        Http::assertSent(function ($request) {
            $url = $request->url();

            return str_contains($url, '/api/v1/business')
                && str_contains($url, 'keyword=Test')
                && str_contains($url, 'limit=10')
                && $request->header('Authorization')[0] === 'Bearer token-123';
        });
    }

    public function test_list_members_with_defaults(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/business*' => Http::response([
                'members' => [],
            ], 200),
        ]);

        $result = $this->client->list('token-123');

        $this->assertEmpty($result['members']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/v1/business')
                && str_contains($request->url(), 'keyword=');
        });
    }

    public function test_get_member_detail(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/business/KHUID5010019082' => Http::response([
                'endpoint_id'   => 'KHUID5010019082',
                'name'          => 'SMALL ENTERPRISE 004',
                'tin'           => 'E006-2400000017',
            ], 200),
        ]);

        $result = $this->client->get('token-123', 'KHUID5010019082');

        $this->assertSame('KHUID5010019082', $result['endpoint_id']);
        $this->assertSame('E006-2400000017', $result['tin']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api-sandbox.e-invoice.gov.kh/api/v1/business/KHUID5010019082'
                && $request->header('Authorization')[0] === 'Bearer token-123';
        });
    }

    public function test_validate_taxpayer(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/business/validate' => Http::response([
                'valid' => true,
                'business_info' => ['name' => 'Valid Business', 'tin' => 'E006-2400000017'],
            ], 200),
        ]);

        $result = $this->client->validateTaxpayer(
            'E006-2400000017',
            'token-123',
            '5010019082',
            'SMALL ENTERPRISE 004',
            'សហគ្រាសធុតតូច០០៤'
        );

        $this->assertTrue($result['valid']);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://api-sandbox.e-invoice.gov.kh/api/v1/business/validate'
                && $data['tin'] === 'E006-2400000017'
                && $data['single_id'] === '5010019082'
                && $data['company_name_en'] === 'SMALL ENTERPRISE 004'
                && $data['company_name_kh'] === 'សហគ្រាសធុតតូច០០៤';
        });
    }
}
