<?php

namespace CamInv\EInvoice\Webhook;

use CamInv\EInvoice\Enums\WebhookEventType;

/**
 * Represents an incoming webhook event from the CamInvoice system.
 *
 * Parses the raw webhook payload into a typed value object, with helper
 * methods to check the specific event type.
 *
 * @see https://developer.e-invoice.gov.kh/receive-update-event/webhook/webhook-events
 */
class WebhookEvent
{
    /** The event type (DOCUMENT.DELIVERED, DOCUMENT.RECEIVED, DOCUMENT.STATUS_UPDATED, ENTITY.REVOKED). */
    public readonly WebhookEventType $type;

    /** The document ID. Null for ENTITY.REVOKED events. */
    public readonly ?string $documentId;

    /** The CamInvoice member endpoint ID. */
    public readonly string $endpointId;

    /** The updated status value. Only present for DOCUMENT.STATUS_UPDATED events. */
    public readonly ?string $status;

    /** The complete raw webhook payload. */
    public readonly array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
        $this->type = WebhookEventType::from($payload['type']);
        $this->documentId = $payload['document_id'] ?? null;
        $this->endpointId = $payload['endpoint_id'];
        $this->status = $payload['status'] ?? null;
    }

    /**
     * Create a WebhookEvent from a raw webhook payload array.
     *
     * @param  array{type: string, document_id?: string, endpoint_id: string, status?: string}  $payload
     * @return self
     */
    public static function fromPayload(array $payload): self
    {
        return new self($payload);
    }

    /**
     * Check if this event indicates a document was delivered to the customer.
     */
    public function isDocumentDelivered(): bool
    {
        return $this->type === WebhookEventType::DOCUMENT_DELIVERED;
    }

    /**
     * Check if this event indicates a document was received and validated.
     */
    public function isDocumentReceived(): bool
    {
        return $this->type === WebhookEventType::DOCUMENT_RECEIVED;
    }

    /**
     * Check if this event indicates a document status was updated by the customer.
     */
    public function isStatusUpdated(): bool
    {
        return $this->type === WebhookEventType::DOCUMENT_STATUS_UPDATED;
    }

    /**
     * Check if this event indicates a business was disconnected (revoked).
     */
    public function isEntityRevoked(): bool
    {
        return $this->type === WebhookEventType::ENTITY_REVOKED;
    }
}
