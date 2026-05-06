<?php

namespace CamInv\EInvoice\Tests\Unit\UBL\Elements;

use CamInv\EInvoice\UBL\Elements\TaxTotal;
use CamInv\EInvoice\Tests\TestCase;
use DOMDocument;

class TaxTotalTest extends TestCase
{
    public function test_single_tax_subtotal(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $root = $doc->createElement('Root');
        $doc->appendChild($root);

        TaxTotal::build($doc, $root, [
            [
                'taxable_amount' => 100.00,
                'tax_amount' => 10.00,
                'tax_category_id' => 'S',
                'percent' => 10.00,
                'tax_scheme_id' => 'VAT',
            ],
        ]);

        $xml = $doc->saveXML();

        $this->assertStringContainsString('<cac:TaxTotal>', $xml);
        $this->assertStringContainsString('<cbc:TaxAmount>10.00</cbc:TaxAmount>', $xml);
        $this->assertStringContainsString('<cac:TaxSubtotal>', $xml);
        $this->assertStringContainsString('<cbc:TaxableAmount>100.00</cbc:TaxableAmount>', $xml);
        $this->assertStringContainsString('<cbc:ID>S</cbc:ID>', $xml);
        $this->assertStringContainsString('<cbc:Percent>10.00</cbc:Percent>', $xml);
        $this->assertStringContainsString('<cac:TaxScheme>', $xml);
        $this->assertStringContainsString('<cbc:ID>VAT</cbc:ID>', $xml);
    }

    public function test_multiple_tax_subtotals(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $root = $doc->createElement('Root');
        $doc->appendChild($root);

        TaxTotal::build($doc, $root, [
            [
                'taxable_amount' => 100.00,
                'tax_amount' => 10.00,
                'tax_category_id' => 'S',
                'percent' => 10.00,
            ],
            [
                'taxable_amount' => 50.00,
                'tax_amount' => 0.00,
                'tax_category_id' => 'Z',
                'percent' => 0.00,
            ],
        ]);

        $xml = $doc->saveXML();

        $subtotalCount = substr_count($xml, '<cac:TaxSubtotal>');
        $this->assertSame(2, $subtotalCount);

        $this->assertStringContainsString('<cbc:TaxAmount>10.00</cbc:TaxAmount>', $xml);
        $this->assertStringContainsString('<cbc:ID>S</cbc:ID>', $xml);
        $this->assertStringContainsString('<cbc:ID>Z</cbc:ID>', $xml);
    }

    public function test_default_values(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $root = $doc->createElement('Root');
        $doc->appendChild($root);

        TaxTotal::build($doc, $root, [
            ['tax_amount' => 5.00],
        ]);

        $xml = $doc->saveXML();

        $this->assertStringContainsString('<cbc:ID>S</cbc:ID>', $xml);
    }
}
