<?php

namespace CamInv\EInvoice\Tests\Unit\UBL\Elements;

use CamInv\EInvoice\UBL\Elements\InvoiceLine;
use CamInv\EInvoice\Tests\TestCase;
use DOMDocument;

class InvoiceLineTest extends TestCase
{
    protected DOMDocument $doc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doc = new DOMDocument('1.0', 'UTF-8');
        $root = $this->doc->createElement('Root');
        $this->doc->appendChild($root);
    }

    public function test_full_line(): void
    {
        $root = $this->doc->documentElement;

        InvoiceLine::build($this->doc, $root, [
            'id' => '1',
            'note' => 'Test note',
            'quantity' => 10,
            'unit_code' => 'EA',
            'line_extension_amount' => 100.00,
            'item' => [
                'name' => 'Test Product',
                'description' => 'A test product',
                'sellers_item_id' => 'SKU-001',
            ],
            'price' => [
                'price_amount' => 10.00,
            ],
        ]);

        $xml = $this->doc->saveXML();

        $this->assertStringContainsString('<cac:InvoiceLine>', $xml);
        $this->assertStringContainsString('<cbc:ID>1</cbc:ID>', $xml);
        $this->assertStringContainsString('<cbc:Note>Test note</cbc:Note>', $xml);
        $this->assertStringContainsString('<cbc:InvoicedQuantity unitCode="EA">10.0000</cbc:InvoicedQuantity>', $xml);
        $this->assertStringContainsString('<cac:Item>', $xml);
        $this->assertStringContainsString('<cbc:Name>Test Product</cbc:Name>', $xml);
        $this->assertStringContainsString('SKU-001', $xml);
        $this->assertStringContainsString('<cac:Price>', $xml);
        $this->assertStringContainsString('<cbc:PriceAmount currencyID="KHR">10.0000</cbc:PriceAmount>', $xml);
    }

    public function test_minimal_line(): void
    {
        $root = $this->doc->documentElement;

        InvoiceLine::build($this->doc, $root, ['id' => '1']);

        $xml = $this->doc->saveXML();

        $this->assertStringContainsString('<cbc:ID>1</cbc:ID>', $xml);
        $this->assertStringNotContainsString('<cac:Item>', $xml);
    }

    public function test_line_with_classification(): void
    {
        $root = $this->doc->documentElement;

        InvoiceLine::build($this->doc, $root, [
            'id' => '1',
            'item' => [
                'name' => 'Item',
                'classifications' => [
                    ['code' => '100000', 'list_id' => 'UNSPSC'],
                ],
            ],
        ]);

        $xml = $this->doc->saveXML();

        $this->assertStringContainsString('<cac:CommodityClassification>', $xml);
        $this->assertStringContainsString('<cbc:ItemClassificationCode listID="UNSPSC">100000</cbc:ItemClassificationCode>', $xml);
    }
}
