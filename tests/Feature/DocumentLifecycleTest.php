<?php

namespace CamInv\EInvoice\Tests\Feature;

use CamInv\EInvoice\Document\DocumentClient;
use CamInv\EInvoice\Enums\DocumentType;
use CamInv\EInvoice\UBL\UBLBuilder;
use CamInv\EInvoice\Webhook\WebhookEvent;
use CamInv\EInvoice\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class DocumentLifecycleTest extends TestCase
{
    public function test_submit_to_webhook_delivered_lifecycle(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/document' => Http::response([
                'documents' => [
                    [
                        'index' => 1,
                        'document_id' => 'uuid-lifecycle-001',
                        'verification_link' => 'https://verify.e-invoice.gov.kh/uuid-lifecycle-001',
                    ],
                ],
                'failed_documents' => [],
            ], 200),
            'api-sandbox.e-invoice.gov.kh/api/v1/document/send' => Http::response([
                'documents' => [['document_id' => 'uuid-lifecycle-001', 'status' => 'SENT']],
            ], 200),
        ]);

        $xml = UBLBuilder::invoice()
            ->setId('INV-LIFECYCLE')
            ->setIssueDate('2026-05-06')
            ->setSupplier([
                'endpoint_id' => 'KHUID00001234',
                'party_name' => 'Test Supplier',
                'postal_address' => ['street_name' => '123 St', 'city_name' => 'PP', 'country' => ['identification_code' => 'KH']],
            'party_tax_scheme' => ['company_id' => 'T001', 'tax_scheme_id' => 'S'],
            'party_legal_entity' => ['registration_name' => 'Test Supplier'],
        ])
        ->setCustomer([
            'endpoint_id' => 'KHUID00005678',
            'party_name' => 'Test Customer',
            'postal_address' => ['street_name' => '456 St', 'city_name' => 'SR', 'country' => ['identification_code' => 'KH']],
            'party_tax_scheme' => ['company_id' => 'T002', 'tax_scheme_id' => 'S'],
                'party_legal_entity' => ['registration_name' => 'Test Customer'],
            ])
            ->addLine(['id' => '1', 'item' => ['name' => 'Product A']])
            ->build();

        $docClient = $this->app->make(DocumentClient::class);

        $submitResult = $docClient->submit(DocumentType::INVOICE, $xml, 'token-abc');
        $this->assertSame('uuid-lifecycle-001', $submitResult['documents'][0]['document_id']);
        $this->assertStringContainsString('verify.e-invoice.gov.kh', $submitResult['documents'][0]['verification_link']);

        $sendResult = $docClient->send(['uuid-lifecycle-001'], 'token-abc');
        $this->assertSame('SENT', $sendResult['documents'][0]['status']);

        $webhookPayload = [
            'type' => 'DOCUMENT.DELIVERED',
            'document_id' => 'uuid-lifecycle-001',
            'endpoint_id' => 'KHUID00001234',
        ];

        $event = WebhookEvent::fromPayload($webhookPayload);
        $this->assertTrue($event->isDocumentDelivered());
        $this->assertSame('uuid-lifecycle-001', $event->documentId);
    }

    public function test_receive_and_accept_flow(): void
    {
        Http::fake([
            'api-sandbox.e-invoice.gov.kh/api/v1/document/uuid-recv-001/xml' => Http::response('<Invoice>received</Invoice>', 200),
            'api-sandbox.e-invoice.gov.kh/api/v1/document/accept' => Http::response(['status' => 'ACCEPTED'], 200),
        ]);

        $docClient = $this->app->make(DocumentClient::class);

        $receivedXml = $docClient->getXml('uuid-recv-001', 'token');
        $this->assertSame('<Invoice>received</Invoice>', $receivedXml);

        $acceptResult = $docClient->accept(['uuid-recv-001'], 'token');
        $this->assertSame('ACCEPTED', $acceptResult['status']);
    }
}
