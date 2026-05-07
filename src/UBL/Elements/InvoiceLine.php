<?php

namespace CamInv\EInvoice\UBL\Elements;

use DOMDocument;
use DOMElement;

/**
 * Builds cac:InvoiceLine UBL elements for invoice line items.
 *
 * Also provides static helpers buildItem() and buildPrice() shared by
 * CreditNoteLine and DebitNoteLine.
 */
class InvoiceLine
{
    public static function build(DOMDocument $doc, DOMElement $parent, array $data): void
    {
        $line = $doc->createElement('cac:InvoiceLine');

        $line->appendChild($doc->createElement('cbc:ID', $data['id'] ?? '1'));

        if (isset($data['quantity'])) {
            $qty = $doc->createElement('cbc:InvoicedQuantity', number_format((float) $data['quantity'], 4, '.', ''));
            $qty->setAttribute('unitCode', $data['unit_code'] ?? 'EA');
            $line->appendChild($qty);
        }

        if (isset($data['line_extension_amount'])) {
            $amt = $doc->createElement('cbc:LineExtensionAmount', number_format((float) $data['line_extension_amount'], 2, '.', ''));
            $amt->setAttribute('currencyID', $data['currency'] ?? 'KHR');
            $line->appendChild($amt);
        }

        if (! empty($data['allowance_charges'])) {
            AllowanceCharge::build($doc, $line, $data['allowance_charges']);
        }

        if (! empty($data['tax_total'])) {
            $taxSubtotals = is_array($data['tax_total']) && isset($data['tax_total'][0])
                ? $data['tax_total']
                : [$data['tax_total']];
            TaxTotal::build($doc, $line, $taxSubtotals);
        }

        if (! empty($data['item'])) {
            self::buildItem($doc, $line, $data['item']);
        }

        if (! empty($data['price'])) {
            self::buildPrice($doc, $line, $data['price'], $data['currency'] ?? 'KHR');
        }

        $parent->appendChild($line);
    }

    public static function buildItem(DOMDocument $doc, DOMElement $parent, array $data): void
    {
        $item = $doc->createElement('cac:Item');

        if (! empty($data['name'])) {
            $item->appendChild($doc->createElement('cbc:Name', $data['name']));
        }

        if (! empty($data['description'])) {
            $item->appendChild($doc->createElement('cbc:Description', $data['description']));
        }

        if (! empty($data['sellers_item_id'])) {
            $sii = $doc->createElement('cac:SellersItemIdentification');
            $sii->appendChild($doc->createElement('cbc:ID', $data['sellers_item_id']));
            $item->appendChild($sii);
        }

        if (! empty($data['standard_item_id'])) {
            $sii = $doc->createElement('cac:StandardItemIdentification');
            $sii->appendChild($doc->createElement('cbc:ID', $data['standard_item_id']));
            $item->appendChild($sii);
        }

        if (! empty($data['origin_country'])) {
            $oc = $doc->createElement('cac:OriginCountry');
            if (! empty($data['origin_country']['identification_code'])) {
                $oc->appendChild($doc->createElement('cbc:IdentificationCode', $data['origin_country']['identification_code']));
            }
            if (! empty($data['origin_country']['name'])) {
                $oc->appendChild($doc->createElement('cbc:Name', $data['origin_country']['name']));
            }
            $item->appendChild($oc);
        }

        $parent->appendChild($item);
    }

    public static function buildPrice(DOMDocument $doc, DOMElement $parent, array $data, string $currency): void
    {
        $price = $doc->createElement('cac:Price');

        if (isset($data['price_amount'])) {
            $amt = $doc->createElement('cbc:PriceAmount', number_format((float) $data['price_amount'], 4, '.', ''));
            $amt->setAttribute('currencyID', $currency);
            $price->appendChild($amt);
        }

        $parent->appendChild($price);
    }
}
