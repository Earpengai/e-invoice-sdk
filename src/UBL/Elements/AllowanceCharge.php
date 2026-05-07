<?php

namespace CamInv\EInvoice\UBL\Elements;

use DOMDocument;
use DOMElement;

/**
 * Builds cac:AllowanceCharge UBL elements (discounts or surcharges).
 */
class AllowanceCharge
{
    public static function build(DOMDocument $doc, DOMElement $parent, array $items): void
    {
        foreach ($items as $data) {
            $ac = $doc->createElement('cac:AllowanceCharge');

            $chargeIndicator = isset($data['charge_indicator']) && $data['charge_indicator'] ? 'true' : 'false';
            $ac->appendChild($doc->createElement('cbc:ChargeIndicator', $chargeIndicator));

            if (! empty($data['allowance_charge_reason'])) {
                $reasons = is_array($data['allowance_charge_reason'])
                    ? $data['allowance_charge_reason']
                    : [$data['allowance_charge_reason']];
                foreach ($reasons as $reason) {
                    $ac->appendChild($doc->createElement('cbc:AllowanceChargeReason', $reason));
                }
            }

            if (isset($data['amount'])) {
                $amt = $doc->createElement('cbc:Amount', number_format((float) $data['amount'], 2, '.', ''));
                if (! empty($data['currency'])) {
                    $amt->setAttribute('currencyID', $data['currency']);
                }
                $ac->appendChild($amt);
            }

            $parent->appendChild($ac);
        }
    }
}
