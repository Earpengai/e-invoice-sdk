<?php

namespace CamInv\EInvoice\UBL\Elements;

use DOMDocument;
use DOMElement;

class InvoiceLine
{
    public static function build(DOMDocument $doc, DOMElement $parent, array $data): void
    {
        $line = $doc->createElement('cac:InvoiceLine');

        $line->appendChild($doc->createElement('cbc:ID', $data['id'] ?? '1'));

        if (! empty($data['note'])) {
            $line->appendChild($doc->createElement('cbc:Note', $data['note']));
        }

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

        if (! empty($data['classifications'])) {
            foreach ($data['classifications'] as $classification) {
                $cc = $doc->createElement('cac:CommodityClassification');
                $cc->appendChild($doc->createElement('cbc:ItemClassificationCode', $classification['code']));
                if (! empty($classification['list_id'])) {
                    $cc->firstChild->setAttribute('listID', $classification['list_id']);
                }
                $item->appendChild($cc);
            }
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
