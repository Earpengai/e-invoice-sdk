<?php

namespace CamInv\EInvoice\UBL\Elements;

use DOMDocument;
use DOMElement;

/**
 * Builds cac:AccountingCustomerParty UBL element.
 */
class CustomerParty
{
    public static function build(DOMDocument $doc, DOMElement $parent, array $data): void
    {
        $customer = $doc->createElement('cac:AccountingCustomerParty');

        Party::append($doc, $customer, $data);

        $parent->appendChild($customer);
    }
}
