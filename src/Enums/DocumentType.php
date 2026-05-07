<?php

namespace CamInv\EInvoice\Enums;

/**
 * Document type identifiers used when submitting documents.
 *
 * @see https://developer.e-invoice.gov.kh/data-type/document-type
 */
enum DocumentType: string
{
    case INVOICE = 'INVOICE';
    case CREDIT_NOTE = 'CREDIT_NOTE';
    case DEBIT_NOTE = 'DEBIT_NOTE';

    public function label(): string
    {
        return match ($this) {
            self::INVOICE => 'Invoice',
            self::CREDIT_NOTE => 'Credit Note',
            self::DEBIT_NOTE => 'Debit Note',
        };
    }

    public function ublCode(): string
    {
        return match ($this) {
            self::INVOICE => '380',
            self::CREDIT_NOTE => '381',
            self::DEBIT_NOTE => '383',
        };
    }

    public function xmlns(): string
    {
        return match ($this) {
            self::INVOICE => 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
            self::CREDIT_NOTE => 'urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2',
            self::DEBIT_NOTE => 'urn:oasis:names:specification:ubl:schema:xsd:DebitNote-2',
        };
    }
}
