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
            'api-sandbox.e-invoice.gov.kh/api/v1/member' => Http::response([
                'members' => [
                    ['endpoint_id' => 'KHUID00001234', 'name' => 'Test Co.'],
                ],
            ], 200),
        ]);

        $result = $this->client->list('token-123');

        $this->assertCount(1, $result['members']);
        $this->assertSame('KHUID00001234', $result['members'][0]['endpoint_id']);
    }

    public function test_validate_taxpayer(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/validate-taxpayer' => Http::response([
                'valid' => true,
                'business_info' => ['name' => 'Valid Business', 'tin' => 'L001123456789'],
            ], 200),
        ]);

        $result = $this->client->validateTaxpayer('L001123456789', 'token-123');

        $this->assertTrue($result['valid']);

        Http::assertSent(function ($request) {
            return $request->data()['tin'] === 'L001123456789';
        });
    }
}
