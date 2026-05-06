<?php

namespace CamInv\EInvoice\Tests\Unit\Webhook;

use CamInv\EInvoice\Client\CamInvClient;
use CamInv\EInvoice\Webhook\WebhookClient;
use CamInv\EInvoice\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class WebhookClientTest extends TestCase
{
    public function test_configure(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/configure/configure-webhook' => Http::response([
                'status' => 'configured',
            ], 200),
        ]);

        $client = new WebhookClient(new CamInvClient('https://api-sandbox.e-invoice.gov.kh'));
        $result = $client->configure('KHUID00001234', 'https://example.com/webhook');

        $this->assertSame('configured', $result['status']);

        Http::assertSent(function ($request) {
            return $request->data()['endpoint_id'] === 'KHUID00001234'
                && $request->data()['webhook_url'] === 'https://example.com/webhook';
        });
    }
}
