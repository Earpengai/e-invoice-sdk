<?php

namespace CamInv\EInvoice\Enums;

enum WebhookEventType: string
{
    case DOCUMENT_DELIVERED = 'DOCUMENT.DELIVERED';
    case DOCUMENT_RECEIVED = 'DOCUMENT.RECEIVED';
    case DOCUMENT_STATUS_UPDATED = 'DOCUMENT.STATUS_UPDATED';
    case ENTITY_REVOKED = 'ENTITY.REVOKED';

    public function label(): string
    {
        return match ($this) {
            self::DOCUMENT_DELIVERED => 'Document Delivered',
            self::DOCUMENT_RECEIVED => 'Document Received',
            self::DOCUMENT_STATUS_UPDATED => 'Status Updated',
            self::ENTITY_REVOKED => 'Entity Revoked',
        };
    }
}
