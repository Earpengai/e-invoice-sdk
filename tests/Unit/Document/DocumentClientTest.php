<?php

namespace CamInv\EInvoice\Tests\Unit\Document;

use CamInv\EInvoice\Client\CamInvClient;
use CamInv\EInvoice\Contracts\TokenStore;
use CamInv\EInvoice\Document\DocumentClient;
use CamInv\EInvoice\Enums\DocumentType;
use CamInv\EInvoice\Support\Config;
use CamInv\EInvoice\Token\TokenManager;
use CamInv\EInvoice\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Mockery;

class DocumentClientTest extends TestCase
{
    protected DocumentClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $store = Mockery::mock(TokenStore::class);
        $config = $this->app->make(Config::class);
        $tokenManager = new TokenManager($store, Mockery::mock(\CamInv\EInvoice\Auth\OAuthService::class), $config);

        $this->client = new DocumentClient(
            new CamInvClient('https://api-sandbox.e-invoice.gov.kh'),
            $tokenManager,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
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

        $result = $this->client->submit(DocumentType::INVOICE, '<Invoice>test</Invoice>', 'token-123');

        $this->assertSame('uuid-123', $result['documents'][0]['document_id']);

        Http::assertSent(function ($request) {
            $data = $request->data();
            $document = $data['documents'][0];

            return $document['document_type'] === 'INVOICE'
                && ! empty($document['document']);
        });
    }

    public function test_send(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/document/send' => Http::response([
                'documents' => [['document_id' => 'uuid-123', 'status' => 'SENT']],
            ], 200),
        ]);

        $result = $this->client->send(['uuid-123'], 'token');
        $this->assertSame('SENT', $result['documents'][0]['status']);
    }

    public function test_accept(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/document/accept' => Http::response([
                'status' => 'ACCEPTED',
            ], 200),
        ]);

        $result = $this->client->accept(['uuid-123'], 'token');
        $this->assertSame('ACCEPTED', $result['status']);
    }

    public function test_reject(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/document/reject' => Http::response([
                'status' => 'REJECTED',
            ], 200),
        ]);

        $result = $this->client->reject(['uuid-123'], 'token', 'Incorrect pricing');
        $this->assertSame('REJECTED', $result['status']);

        Http::assertSent(function ($request) {
            return $request->data()['reason'] === 'Incorrect pricing';
        });
    }

    public function test_reject_without_reason(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/document/reject' => Http::response(['status' => 'REJECTED'], 200),
        ]);

        $result = $this->client->reject(['uuid-123'], 'token');
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

    public function test_update_status(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/document/status' => Http::response([
                'documents' => [
                    ['document_id' => 'uuid-123', 'status' => 'ACCEPTED'],
                ],
            ], 200),
        ]);

        $result = $this->client->updateStatus(['uuid-123'], 'ACCEPTED', 'token');

        $this->assertSame('ACCEPTED', $result['documents'][0]['status']);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $data['documents'] === ['uuid-123']
                && $data['status'] === 'ACCEPTED';
        });
    }

    public function test_list(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/document?type=send&page=1&size=20' => Http::response([
                'data' => [
                    ['document_id' => 'uuid-123', 'status' => 'SENT'],
                ],
                'total' => 1,
            ], 200),
        ]);

        $result = $this->client->list('token');

        $this->assertCount(1, $result['data']);
        $this->assertSame('uuid-123', $result['data'][0]['document_id']);
    }

    public function test_list_with_document_type(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/document?type=received&page=2&size=10&document_type=INVOICE' => Http::response([
                'data' => [],
                'total' => 0,
            ], 200),
        ]);

        $result = $this->client->list('token', 'received', 2, 10, 'INVOICE');

        $this->assertCount(0, $result['data']);
    }
}
