<?php

namespace CamInv\EInvoice\Enums;

/**
 * Indicates whether a document was sent or received.
 */
enum DocumentDirection: string
{
    case SENT = 'SENT';
    case RECEIVED = 'RECEIVED';

    public function label(): string
    {
        return match ($this) {
            self::SENT => 'Sent',
            self::RECEIVED => 'Received',
        };
    }
}
