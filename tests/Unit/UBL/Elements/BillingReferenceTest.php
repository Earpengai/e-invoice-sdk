<?php

namespace CamInv\EInvoice\Tests\Unit\UBL\Elements;

use CamInv\EInvoice\UBL\Elements\BillingReference;
use CamInv\EInvoice\Tests\TestCase;
use DOMDocument;

class BillingReferenceTest extends TestCase
{
    public function test_build_generates_correct_xml(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $root = $doc->createElement('Root');
        $doc->appendChild($root);

        BillingReference::build($doc, $root, 'INV-ORIG-001');

        $xml = $doc->saveXML();

        $this->assertStringContainsString('<cac:BillingReference>', $xml);
        $this->assertStringContainsString('<cac:InvoiceDocumentReference>', $xml);
        $this->assertStringContainsString('<cbc:ID>INV-ORIG-001</cbc:ID>', $xml);
    }
}
