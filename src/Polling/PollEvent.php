<?php

namespace CamInv\EInvoice\Polling;

/**
 * Represents a polled document update event.
 *
 * The type field indicates whether the event relates to a "SEND" or
 * "RECEIVE" document operation. Use the isSend() and isReceive()
 * helper methods to check the direction.
 *
 * @see https://developer.e-invoice.gov.kh/receive-update-event/polling/getting-started
 */
class PollEvent
{
    /** The unique document identifier in CamInvoice. */
    public readonly string $documentId;

    /** ISO 8601 timestamp of when the document was last updated. */
    public readonly string $updatedAt;

    /** Event direction: "SEND" or "RECEIVE". */
    public readonly string $type;

    /** The complete raw poll event payload. */
    public readonly array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
        $this->documentId = $payload['document_id'];
        $this->updatedAt = $payload['updated_at'];
        $this->type = $payload['type'];
    }

    /**
     * Create a PollEvent from a raw API response array.
     *
     * @param  array{document_id: string, updated_at: string, type: string}  $payload
     * @return self
     */
    public static function fromArray(array $payload): self
    {
        return new self($payload);
    }

    /**
     * Check if this event represents a sent document (outgoing).
     */
    public function isSend(): bool
    {
        return $this->type === 'SEND';
    }

    /**
     * Check if this event represents a received document (incoming).
     */
    public function isReceive(): bool
    {
        return $this->type === 'RECEIVE';
    }
}
