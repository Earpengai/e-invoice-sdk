<?php

namespace CamInv\EInvoice\Polling;

class PollEvent
{
    public readonly string $documentId;

    public readonly string $updatedAt;

    public readonly string $type;

    public readonly array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
        $this->documentId = $payload['document_id'];
        $this->updatedAt = $payload['updated_at'];
        $this->type = $payload['type'];
    }

    public static function fromArray(array $payload): self
    {
        return new self($payload);
    }

    public function isSend(): bool
    {
        return $this->type === 'SEND';
    }

    public function isReceive(): bool
    {
        return $this->type === 'RECEIVE';
    }
}
