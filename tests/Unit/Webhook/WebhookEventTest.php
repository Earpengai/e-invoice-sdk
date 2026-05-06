<?php

namespace CamInv\EInvoice\Tests\Unit\Webhook;

use CamInv\EInvoice\Enums\WebhookEventType;
use CamInv\EInvoice\Webhook\WebhookEvent;
use CamInv\EInvoice\Tests\TestCase;

class WebhookEventTest extends TestCase
{
    public function test_parse_document_delivered(): void
    {
        $payload = [
            'type' => 'DOCUMENT.DELIVERED',
            'document_id' => 'uuid-123',
            'endpoint_id' => 'KHUID00001234',
        ];

        $event = WebhookEvent::fromPayload($payload);

        $this->assertSame(WebhookEventType::DOCUMENT_DELIVERED, $event->type);
        $this->assertSame('uuid-123', $event->documentId);
        $this->assertSame('KHUID00001234', $event->endpointId);
        $this->assertNull($event->status);
        $this->assertTrue($event->isDocumentDelivered());
        $this->assertFalse($event->isDocumentReceived());
        $this->assertFalse($event->isStatusUpdated());
        $this->assertFalse($event->isEntityRevoked());
    }

    public function test_parse_document_received(): void
    {
        $payload = [
            'type' => 'DOCUMENT.RECEIVED',
            'document_id' => 'uuid-456',
            'endpoint_id' => 'KHUID00001234',
            'status' => 'VALID',
        ];

        $event = WebhookEvent::fromPayload($payload);

        $this->assertSame(WebhookEventType::DOCUMENT_RECEIVED, $event->type);
        $this->assertSame('uuid-456', $event->documentId);
        $this->assertSame('VALID', $event->status);
        $this->assertTrue($event->isDocumentReceived());
        $this->assertFalse($event->isDocumentDelivered());
    }

    public function test_parse_status_updated(): void
    {
        $payload = [
            'type' => 'DOCUMENT.STATUS_UPDATED',
            'document_id' => 'uuid-789',
            'endpoint_id' => 'KHUID00001234',
            'status' => 'ACCEPTED',
        ];

        $event = WebhookEvent::fromPayload($payload);

        $this->assertSame(WebhookEventType::DOCUMENT_STATUS_UPDATED, $event->type);
        $this->assertSame('ACCEPTED', $event->status);
        $this->assertTrue($event->isStatusUpdated());
    }

    public function test_parse_entity_revoked(): void
    {
        $payload = [
            'type' => 'ENTITY.REVOKED',
            'endpoint_id' => 'KHUID00001234',
        ];

        $event = WebhookEvent::fromPayload($payload);

        $this->assertSame(WebhookEventType::ENTITY_REVOKED, $event->type);
        $this->assertNull($event->documentId);
        $this->assertTrue($event->isEntityRevoked());
    }

    public function test_invalid_type_throws(): void
    {
        $this->expectException(\ValueError::class);

        WebhookEvent::fromPayload([
            'type' => 'INVALID.TYPE',
            'endpoint_id' => 'KHUID00001234',
        ]);
    }

    public function test_stores_full_payload(): void
    {
        $payload = ['type' => 'DOCUMENT.DELIVERED', 'document_id' => 'u-1', 'endpoint_id' => 'EP1', 'extra' => 'data'];
        $event = WebhookEvent::fromPayload($payload);

        $this->assertSame($payload, $event->payload);
    }
}
