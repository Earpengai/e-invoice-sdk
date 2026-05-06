<?php

namespace CamInv\EInvoice\Webhook;

use CamInv\EInvoice\Enums\WebhookEventType;

class WebhookEvent
{
    public readonly WebhookEventType $type;

    public readonly ?string $documentId;

    public readonly string $endpointId;

    public readonly ?string $status;

    public readonly array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
        $this->type = WebhookEventType::from($payload['type']);
        $this->documentId = $payload['document_id'] ?? null;
        $this->endpointId = $payload['endpoint_id'];
        $this->status = $payload['status'] ?? null;
    }

    public static function fromPayload(array $payload): self
    {
        return new self($payload);
    }

    public function isDocumentDelivered(): bool
    {
        return $this->type === WebhookEventType::DOCUMENT_DELIVERED;
    }

    public function isDocumentReceived(): bool
    {
        return $this->type === WebhookEventType::DOCUMENT_RECEIVED;
    }

    public function isStatusUpdated(): bool
    {
        return $this->type === WebhookEventType::DOCUMENT_STATUS_UPDATED;
    }

    public function isEntityRevoked(): bool
    {
        return $this->type === WebhookEventType::ENTITY_REVOKED;
    }
}
