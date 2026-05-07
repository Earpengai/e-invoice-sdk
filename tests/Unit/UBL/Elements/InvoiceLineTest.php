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
            'quantity' => 10,
            'unit_code' => 'EA',
            'line_extension_amount' => 100.00,
            'item' => [
                'name' => 'Test Product',
                'description' => 'A test product',
                'sellers_item_id' => 'SKU-001',
                'standard_item_id' => 'STD-001',
                'origin_country' => [
                    'identification_code' => 'KH',
                    'name' => 'Cambodia',
                ],
            ],
            'price' => [
                'price_amount' => 10.00,
            ],
        ]);

        $xml = $this->doc->saveXML();

        $this->assertStringContainsString('<cac:InvoiceLine>', $xml);
        $this->assertStringContainsString('<cbc:ID>1</cbc:ID>', $xml);
        $this->assertStringContainsString('<cbc:InvoicedQuantity unitCode="EA">10.0000</cbc:InvoicedQuantity>', $xml);
        $this->assertStringContainsString('<cac:Item>', $xml);
        $this->assertStringContainsString('<cbc:Name>Test Product</cbc:Name>', $xml);
        $this->assertStringContainsString('SKU-001', $xml);
        $this->assertStringContainsString('STD-001', $xml);
        $this->assertStringContainsString('<cac:StandardItemIdentification>', $xml);
        $this->assertStringContainsString('<cac:OriginCountry>', $xml);
        $this->assertStringContainsString('<cbc:IdentificationCode>KH</cbc:IdentificationCode>', $xml);
        $this->assertStringContainsString('<cbc:Name>Cambodia</cbc:Name>', $xml);
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

    public function test_line_with_allowance_charge(): void
    {
        $root = $this->doc->documentElement;

        InvoiceLine::build($this->doc, $root, [
            'id' => '1',
            'allowance_charges' => [
                ['charge_indicator' => false, 'allowance_charge_reason' => 'Discount', 'amount' => 5.00],
            ],
        ]);

        $xml = $this->doc->saveXML();

        $this->assertStringContainsString('<cac:AllowanceCharge>', $xml);
        $this->assertStringContainsString('<cbc:ChargeIndicator>false</cbc:ChargeIndicator>', $xml);
        $this->assertStringContainsString('<cbc:AllowanceChargeReason>Discount</cbc:AllowanceChargeReason>', $xml);
    }
}
