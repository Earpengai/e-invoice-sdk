<?php

namespace CamInv\EInvoice\UBL\Elements;

use DOMDocument;
use DOMElement;

class MonetaryTotal
{
    public static function build(DOMDocument $doc, DOMElement $parent, array $data, string $elementName = 'LegalMonetaryTotal'): void
    {
        $total = $doc->createElement('cac:' . $elementName);

        $fields = [
            'line_extension_amount' => 'cbc:LineExtensionAmount',
            'tax_exclusive_amount' => 'cbc:TaxExclusiveAmount',
            'tax_inclusive_amount' => 'cbc:TaxInclusiveAmount',
            'allowance_total_amount' => 'cbc:AllowanceTotalAmount',
            'charge_total_amount' => 'cbc:ChargeTotalAmount',
            'prepaid_amount' => 'cbc:PrepaidAmount',
            'payable_rounding_amount' => 'cbc:PayableRoundingAmount',
            'payable_amount' => 'cbc:PayableAmount',
        ];

        foreach ($fields as $key => $element) {
            if (isset($data[$key])) {
                $attr = ['currencyID' => $data['currency'] ?? 'KHR'];
                $el = $doc->createElement($element, number_format((float) $data[$key], 2, '.', ''));
                $el->setAttribute('currencyID', $attr['currencyID']);
                $total->appendChild($el);
            }
        }

        $parent->appendChild($total);
    }
}
