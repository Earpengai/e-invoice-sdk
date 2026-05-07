<?php

namespace CamInv\EInvoice\UBL\Elements;

use DOMDocument;
use DOMElement;

class TaxExchangeRate
{
    public static function build(DOMDocument $doc, DOMElement $parent, array $data): void
    {
        $rate = $doc->createElement('cac:TaxExchangeRate');

        if (! empty($data['source_currency_code'])) {
            $rate->appendChild($doc->createElement('cbc:SourceCurrencyCode', $data['source_currency_code']));
        }

        if (! empty($data['target_currency_code'])) {
            $rate->appendChild($doc->createElement('cbc:TargetCurrencyCode', $data['target_currency_code']));
        }

        if (isset($data['calculation_rate'])) {
            $rate->appendChild($doc->createElement('cbc:CalculationRate', (string) $data['calculation_rate']));
        }

        $parent->appendChild($rate);
    }
}
