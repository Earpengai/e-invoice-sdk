<?php

namespace CamInv\EInvoice\UBL\Elements;

use DOMDocument;
use DOMElement;

class SupplierParty
{
    public static function build(DOMDocument $doc, DOMElement $parent, array $data): void
    {
        $supplier = $doc->createElement('cac:AccountingSupplierParty');

        Party::append($doc, $supplier, $data);

        $parent->appendChild($supplier);
    }
}
