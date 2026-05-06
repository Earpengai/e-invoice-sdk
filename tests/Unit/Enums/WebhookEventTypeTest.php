<?php

namespace CamInv\EInvoice\Tests\Unit\Enums;

use CamInv\EInvoice\Enums\WebhookEventType;
use CamInv\EInvoice\Tests\TestCase;

class WebhookEventTypeTest extends TestCase
{
    public function test_values_and_labels(): void
    {
        $this->assertSame('DOCUMENT.DELIVERED', WebhookEventType::DOCUMENT_DELIVERED->value);
        $this->assertSame('Document Delivered', WebhookEventType::DOCUMENT_DELIVERED->label());

        $this->assertSame('DOCUMENT.RECEIVED', WebhookEventType::DOCUMENT_RECEIVED->value);
        $this->assertSame('Document Received', WebhookEventType::DOCUMENT_RECEIVED->label());

        $this->assertSame('DOCUMENT.STATUS_UPDATED', WebhookEventType::DOCUMENT_STATUS_UPDATED->value);
        $this->assertSame('Status Updated', WebhookEventType::DOCUMENT_STATUS_UPDATED->label());

        $this->assertSame('ENTITY.REVOKED', WebhookEventType::ENTITY_REVOKED->value);
        $this->assertSame('Entity Revoked', WebhookEventType::ENTITY_REVOKED->label());
    }

    public function test_from_valid_string(): void
    {
        $this->assertSame(
            WebhookEventType::DOCUMENT_DELIVERED,
            WebhookEventType::from('DOCUMENT.DELIVERED')
        );
    }

    public function test_from_invalid_string_throws(): void
    {
        $this->expectException(\ValueError::class);
        WebhookEventType::from('INVALID.EVENT');
    }
}
