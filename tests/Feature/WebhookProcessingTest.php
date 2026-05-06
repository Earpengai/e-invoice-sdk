<?php

namespace CamInv\EInvoice\Tests\Feature;

use CamInv\EInvoice\Webhook\WebhookEvent;
use CamInv\EInvoice\Enums\WebhookEventType;
use CamInv\EInvoice\Tests\TestCase;

class WebhookProcessingTest extends TestCase
{
    public function test_all_four_event_types_from_fixtures(): void
    {
        $deliveredPayload = json_decode(
            file_get_contents(__DIR__ . '/../Fixtures/webhook/document-delivered.json'),
            true
        );
        $event = new WebhookEvent($deliveredPayload);
        $this->assertSame(WebhookEventType::DOCUMENT_DELIVERED, $event->type);
        $this->assertTrue($event->isDocumentDelivered());
        $this->assertSame('uuid-test-123-abc-def-456789', $event->documentId);
        $this->assertSame('KHUID00001234', $event->endpointId);
        $this->assertNull($event->status);

        $receivedPayload = json_decode(
            file_get_contents(__DIR__ . '/../Fixtures/webhook/document-received.json'),
            true
        );
        $event = new WebhookEvent($receivedPayload);
        $this->assertSame(WebhookEventType::DOCUMENT_RECEIVED, $event->type);
        $this->assertTrue($event->isDocumentReceived());
        $this->assertSame('uuid-test-456-def-abc-789012', $event->documentId);
        $this->assertSame('VALID', $event->status);

        $statusPayload = json_decode(
            file_get_contents(__DIR__ . '/../Fixtures/webhook/status-updated.json'),
            true
        );
        $event = new WebhookEvent($statusPayload);
        $this->assertSame(WebhookEventType::DOCUMENT_STATUS_UPDATED, $event->type);
        $this->assertTrue($event->isStatusUpdated());
        $this->assertSame('ACCEPTED', $event->status);

        $revokedPayload = json_decode(
            file_get_contents(__DIR__ . '/../Fixtures/webhook/entity-revoked.json'),
            true
        );
        $event = new WebhookEvent($revokedPayload);
        $this->assertSame(WebhookEventType::ENTITY_REVOKED, $event->type);
        $this->assertTrue($event->isEntityRevoked());
        $this->assertNull($event->documentId);
    }

    public function test_event_boolean_methods_are_mutually_exclusive(): void
    {
        $event = new WebhookEvent([
            'type' => 'DOCUMENT.DELIVERED',
            'document_id' => 'uuid-123',
            'endpoint_id' => 'KHUID00001234',
        ]);

        $this->assertTrue($event->isDocumentDelivered());
        $this->assertFalse($event->isDocumentReceived());
        $this->assertFalse($event->isStatusUpdated());
        $this->assertFalse($event->isEntityRevoked());
    }

    public function test_event_with_extra_fields_preserves_full_payload(): void
    {
        $payload = [
            'type' => 'DOCUMENT.DELIVERED',
            'document_id' => 'uuid-123',
            'endpoint_id' => 'KHUID00001234',
            'custom_field' => 'custom-value',
            'metadata' => ['key' => 'value'],
        ];

        $event = new WebhookEvent($payload);

        $this->assertArrayHasKey('custom_field', $event->payload);
        $this->assertSame('custom-value', $event->payload['custom_field']);
        $this->assertSame(['key' => 'value'], $event->payload['metadata']);
    }
}
