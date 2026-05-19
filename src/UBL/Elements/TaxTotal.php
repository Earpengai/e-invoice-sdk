<?php

namespace CamInv\EInvoice\UBL\Elements;

use DOMDocument;
use DOMElement;

/**
 * Builds cac:TaxTotal UBL element with tax subtotals and category/scheme classification.
 */
class TaxTotal
{
    public static function build(DOMDocument $doc, DOMElement $parent, array $taxSubtotals): void
    {
        $total = $doc->createElement('cac:TaxTotal');

        $totalTaxAmount = 0.0;

        foreach ($taxSubtotals as $subtotal) {
            $totalTaxAmount += (float) ($subtotal['tax_amount'] ?? 0);
        }

        $taxAmountEl = $doc->createElement('cbc:TaxAmount', number_format($totalTaxAmount, 2, '.', ''));
        $taxAmountEl->setAttribute('currencyID', ($taxSubtotals[0]['currency'] ?? $taxSubtotals[array_key_first($taxSubtotals)]['currency'] ?? 'KHR'));
        $total->appendChild($taxAmountEl);

        foreach ($taxSubtotals as $subtotal) {
            $currency = $subtotal['currency'] ?? 'KHR';
            $total->appendChild(self::buildTaxSubtotal($doc, $subtotal, $currency));
        }

        $parent->appendChild($total);
    }

    protected static function buildTaxSubtotal(DOMDocument $doc, array $data, string $currency = 'KHR'): DOMElement
    {
        $subtotal = $doc->createElement('cac:TaxSubtotal');

        if (isset($data['taxable_amount'])) {
            $amt = $doc->createElement('cbc:TaxableAmount', number_format($data['taxable_amount'], 2, '.', ''));
            $amt->setAttribute('currencyID', $currency);
            $subtotal->appendChild($amt);
        }

        if (isset($data['tax_amount'])) {
            $amt = $doc->createElement('cbc:TaxAmount', number_format($data['tax_amount'], 2, '.', ''));
            $amt->setAttribute('currencyID', $currency);
            $subtotal->appendChild($amt);
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
