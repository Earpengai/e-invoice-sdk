<?php

namespace CamInv\EInvoice\UBL\Elements;

use DOMDocument;
use DOMElement;

class PaymentTerms
{
    public static function build(DOMDocument $doc, DOMElement $parent, array $data): void
    {
        if (empty($data)) {
            return;
        }

        $terms = $doc->createElement('cac:PaymentTerms');

        if (! empty($data['note'])) {
            $terms->appendChild($doc->createElement('cbc:Note', $data['note']));
        }

        if (! empty($data['settlement_discount_percent'])) {
            $terms->appendChild($doc->createElement('cbc:SettlementDiscountPercent', number_format((float) $data['settlement_discount_percent'], 2, '.', '')));
        }

        if (! empty($data['amount'])) {
            $amt = $doc->createElement('cbc:Amount', number_format((float) $data['amount'], 2, '.', ''));
            $amt->setAttribute('currencyID', $data['currency'] ?? 'KHR');
            $terms->appendChild($amt);
        }

        $parent->appendChild($terms);
    }
}
