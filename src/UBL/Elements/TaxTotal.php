<?php

namespace CamInv\EInvoice\UBL\Elements;

use DOMDocument;
use DOMElement;

class TaxTotal
{
    public static function build(DOMDocument $doc, DOMElement $parent, array $taxSubtotals): void
    {
        $total = $doc->createElement('cac:TaxTotal');

        $totalTaxAmount = 0.0;

        foreach ($taxSubtotals as $subtotal) {
            $totalTaxAmount += (float) ($subtotal['tax_amount'] ?? 0);
            $total->appendChild(self::buildTaxSubtotal($doc, $subtotal));
        }

        $total->appendChild($doc->createElement('cbc:TaxAmount', number_format($totalTaxAmount, 2, '.', '')));

        $parent->appendChild($total);
    }

    protected static function buildTaxSubtotal(DOMDocument $doc, array $data): DOMElement
    {
        $subtotal = $doc->createElement('cac:TaxSubtotal');

        if (isset($data['taxable_amount'])) {
            $subtotal->appendChild($doc->createElement('cbc:TaxableAmount', number_format($data['taxable_amount'], 2, '.', '')));
        }

        if (isset($data['tax_amount'])) {
            $subtotal->appendChild($doc->createElement('cbc:TaxAmount', number_format($data['tax_amount'], 2, '.', '')));
        }

        $category = $doc->createElement('cac:TaxCategory');
        $category->appendChild($doc->createElement('cbc:ID', $data['tax_category_id'] ?? 'S'));

        if (isset($data['percent'])) {
            $category->appendChild($doc->createElement('cbc:Percent', number_format($data['percent'], 2, '.', '')));
        }

        $scheme = $doc->createElement('cac:TaxScheme');
        $scheme->appendChild($doc->createElement('cbc:ID', $data['tax_scheme_id'] ?? 'VAT'));
        $category->appendChild($scheme);

        $subtotal->appendChild($category);

        return $subtotal;
    }
}
