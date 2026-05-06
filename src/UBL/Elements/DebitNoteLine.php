<?php

namespace CamInv\EInvoice\UBL\Elements;

use DOMDocument;
use DOMElement;

class DebitNoteLine
{
    public static function build(DOMDocument $doc, DOMElement $parent, array $data): void
    {
        $line = $doc->createElement('cac:DebitNoteLine');

        $line->appendChild($doc->createElement('cbc:ID', $data['id'] ?? '1'));

        if (! empty($data['note'])) {
            $line->appendChild($doc->createElement('cbc:Note', $data['note']));
        }

        if (isset($data['quantity'])) {
            $qty = $doc->createElement('cbc:DebitedQuantity', number_format((float) $data['quantity'], 4, '.', ''));
            $qty->setAttribute('unitCode', $data['unit_code'] ?? 'EA');
            $line->appendChild($qty);
        }

        if (isset($data['line_extension_amount'])) {
            $amt = $doc->createElement('cbc:LineExtensionAmount', number_format((float) $data['line_extension_amount'], 2, '.', ''));
            $amt->setAttribute('currencyID', $data['currency'] ?? 'KHR');
            $line->appendChild($amt);
        }

        if (! empty($data['tax_total'])) {
            $taxSubtotals = isset($data['tax_total'][0]) ? $data['tax_total'] : [$data['tax_total']];
            TaxTotal::build($doc, $line, $taxSubtotals);
        }

        if (! empty($data['item'])) {
            InvoiceLine::buildItem($doc, $line, $data['item']);
        }

        if (! empty($data['price'])) {
            InvoiceLine::buildPrice($doc, $line, $data['price'], $data['currency'] ?? 'KHR');
        }

        $parent->appendChild($line);
    }
}
