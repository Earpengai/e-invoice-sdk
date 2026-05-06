<?php

namespace CamInv\EInvoice\Tests\Unit\Document;

use CamInv\EInvoice\Client\CamInvClient;
use CamInv\EInvoice\Document\DocumentClient;
use CamInv\EInvoice\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class DocumentClientTest extends TestCase
{
    protected DocumentClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new DocumentClient(
            new CamInvClient('https://api-sandbox.e-invoice.gov.kh')
        );
    }

    public function test_submit(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/document' => Http::response([
                'documents' => [
                    ['document_id' => 'uuid-123', 'verification_link' => 'https://verify.example.com/uuid-123'],
                ],
                'failed_documents' => [],
            ], 200),
        ]);

        $result = $this->client->submit('<Invoice>test</Invoice>', 'token-123');

        $this->assertSame('uuid-123', $result['documents'][0]['document_id']);

        Http::assertSent(function ($request) {
            $data = $request->data();
            $document = $data['documents'][0];

            return $document['format'] === 'UBL'
                && ! empty($document['document'])
                && $document['long_time_deliver'] === 2592000;
        });
    }

    public function test_submit_with_custom_ttl(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/document' => Http::response([
                'documents' => [['document_id' => 'uuid-456']],
                'failed_documents' => [],
            ], 200),
        ]);

        $this->client->submit('<Invoice>test</Invoice>', 'token', 86400);

        Http::assertSent(function ($request) {
            return $request->data()['documents'][0]['long_time_deliver'] === 86400;
        });
    }

    public function test_send(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/document/send' => Http::response([
                'documents' => [['document_id' => 'uuid-123', 'status' => 'SENT']],
            ], 200),
        ]);

        $result = $this->client->send('uuid-123', 'KHUID00005678', 'token');
        $this->assertSame('SENT', $result['documents'][0]['status']);
    }

    public function test_accept(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/document/uuid-123/accept' => Http::response([
                'status' => 'ACCEPTED',
            ], 200),
        ]);

        $result = $this->client->accept('uuid-123', 'token');
        $this->assertSame('ACCEPTED', $result['status']);
    }

    public function test_reject(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/document/uuid-123/reject' => Http::response([
                'status' => 'REJECTED',
            ], 200),
        ]);

        $result = $this->client->reject('uuid-123', 'token', 'Incorrect pricing');
        $this->assertSame('REJECTED', $result['status']);

        Http::assertSent(function ($request) {
            return $request->data()['reason'] === 'Incorrect pricing';
        });
    }

    public function test_reject_without_reason(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/document/uuid-123/reject' => Http::response(['status' => 'REJECTED'], 200),
        ]);

        $result = $this->client->reject('uuid-123', 'token');
        $this->assertSame('REJECTED', $result['status']);
    }

    public function test_get_xml(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/document/uuid-123/xml' => Http::response('<Invoice>...</Invoice>', 200),
        ]);

        $xml = $this->client->getXml('uuid-123', 'token');
        $this->assertSame('<Invoice>...</Invoice>', $xml);
    }

    public function test_get_pdf(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/document/uuid-123/pdf' => Http::response('%PDF-1.4 content', 200),
        ]);

        $pdf = $this->client->getPdf('uuid-123', 'token');
        $this->assertSame('%PDF-1.4 content', $pdf);
    }

    public function test_get_detail(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/document/uuid-123' => Http::response([
                'document_id' => 'uuid-123',
                'status' => 'SUBMITTING',
            ], 200),
        ]);

        $detail = $this->client->getDetail('uuid-123', 'token');
        $this->assertSame('SUBMITTING', $detail['status']);
    }
}
