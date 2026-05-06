<?php

namespace CamInv\EInvoice\UBL;

use CamInv\EInvoice\UBL\Builders\CreditNoteBuilder;
use CamInv\EInvoice\UBL\Builders\DebitNoteBuilder;
use CamInv\EInvoice\UBL\Builders\InvoiceBuilder;

class UBLBuilder
{
    public static function invoice(array $options = []): InvoiceBuilder
    {
        return new InvoiceBuilder($options);
    }

    public static function creditNote(array $options = []): CreditNoteBuilder
    {
        return new CreditNoteBuilder($options);
    }

    public static function debitNote(array $options = []): DebitNoteBuilder
    {
        return new DebitNoteBuilder($options);
    }
}
