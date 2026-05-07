<?php

namespace CamInv\EInvoice\Tests\Unit\UBL\Elements;

use CamInv\EInvoice\UBL\Elements\MonetaryTotal;
use CamInv\EInvoice\Tests\TestCase;
use DOMDocument;

class MonetaryTotalTest extends TestCase
{
    public function test_all_fields(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $root = $doc->createElement('Root');
        $doc->appendChild($root);

        MonetaryTotal::build($doc, $root, [
            'line_extension_amount' => 100.00,
            'tax_exclusive_amount' => 100.00,
            'tax_inclusive_amount' => 110.00,
            'allowance_total_amount' => 5.00,
            'charge_total_amount' => 2.00,
            'prepaid_amount' => 10.00,
            'payable_rounding_amount' => 0.05,
            'payable_amount' => 107.05,
            'currency' => 'KHR',
        ]);

        $xml = $doc->saveXML();

        $this->assertStringContainsString('<cac:LegalMonetaryTotal>', $xml);
        $this->assertStringContainsString('<cbc:LineExtensionAmount currencyID="KHR">100.00</cbc:LineExtensionAmount>', $xml);
        $this->assertStringContainsString('<cbc:PayableAmount currencyID="KHR">107.05</cbc:PayableAmount>', $xml);
    }

    public function test_requested_monetary_total(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $root = $doc->createElement('Root');
        $doc->appendChild($root);

        MonetaryTotal::build($doc, $root, [
            'line_extension_amount' => 50.00,
            'payable_amount' => 55.00,
        ], 'RequestedMonetaryTotal');

        $xml = $doc->saveXML();

        $this->assertStringContainsString('<cac:RequestedMonetaryTotal>', $xml);
        $this->assertStringNotContainsString('<cac:LegalMonetaryTotal>', $xml);
    }

    public function test_partial_fields(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $root = $doc->createElement('Root');
        $doc->appendChild($root);

        MonetaryTotal::build($doc, $root, [
            'payable_amount' => 55.00,
        ]);

        $xml = $doc->saveXML();

        $this->assertStringContainsString('<cbc:PayableAmount currencyID="KHR">55.00</cbc:PayableAmount>', $xml);
        $this->assertStringNotContainsString('LineExtensionAmount', $xml);
    }

    public function test_decimal_formatting(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $root = $doc->createElement('Root');
        $doc->appendChild($root);

        MonetaryTotal::build($doc, $root, [
            'payable_amount' => 99.999,
        ]);

        $xml = $doc->saveXML();
        $this->assertStringContainsString('100.00', $xml);
    }
}
