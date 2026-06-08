<?php

namespace CamInv\EInvoice\UBL\Elements;

use DOMDocument;
use DOMElement;

/**
 * Builds cac:BillingReference UBL element linking a credit/debit note to an original invoice.
 */
class BillingReference
{
    public static function build(DOMDocument $doc, DOMElement $parent, string $originalInvoiceId, ?string $originalInvoiceUUID = null): void
    {
        $ref = $doc->createElement('cac:BillingReference');
        $docRef = $doc->createElement('cac:InvoiceDocumentReference');
        $docRef->appendChild($doc->createElement('cbc:ID', $originalInvoiceId));
        $ref->appendChild($docRef);

        if ($originalInvoiceUUID) {
            $uuidElement = $doc->createElement('cbc:UUID', $originalInvoiceUUID);
            $docRef->appendChild($uuidElement);
        }

        $parent->appendChild($ref);
    }
}
