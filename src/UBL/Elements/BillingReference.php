<?php

namespace CamInv\EInvoice\UBL\Elements;

use DOMDocument;
use DOMElement;

class BillingReference
{
    public static function build(DOMDocument $doc, DOMElement $parent, string $originalInvoiceId): void
    {
        $ref = $doc->createElement('cac:BillingReference');
        $docRef = $doc->createElement('cac:InvoiceDocumentReference');
        $docRef->appendChild($doc->createElement('cbc:ID', $originalInvoiceId));
        $ref->appendChild($docRef);

        $parent->appendChild($ref);
    }
}
