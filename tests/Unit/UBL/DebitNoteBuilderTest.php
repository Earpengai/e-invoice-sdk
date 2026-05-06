<?php

namespace CamInv\EInvoice\Tests\Unit\UBL;

use CamInv\EInvoice\UBL\UBLBuilder;
use CamInv\EInvoice\Tests\TestCase;

class DebitNoteBuilderTest extends TestCase
{
    public function test_debit_note_includes_billing_reference(): void
    {
        $xml = UBLBuilder::debitNote()
            ->setId('DN-001')
            ->setIssueDate('2026-05-06')
            ->setOriginalInvoiceId('INV-001')
            ->setSupplier(['party_name' => 'Supplier'])
            ->setCustomer(['party_name' => 'Customer'])
            ->build();

        $this->assertStringContainsString('<DebitNote', $xml);
        $this->assertStringContainsString('xmlns="urn:oasis:names:specification:ubl:schema:xsd:DebitNote-2"', $xml);
        $this->assertStringContainsString('<cac:BillingReference>', $xml);
        $this->assertStringContainsString('<cbc:ID>INV-001</cbc:ID>', $xml);
    }

    public function test_debit_note_line_with_debited_quantity(): void
    {
        $xml = UBLBuilder::debitNote()
            ->setId('DN-002')
            ->setIssueDate('2026-05-06')
            ->setOriginalInvoiceId('INV-001')
            ->setSupplier(['party_name' => 'Supplier'])
            ->setCustomer(['party_name' => 'Customer'])
            ->addLine([
                'id' => '1',
                'quantity' => 3,
                'unit_code' => 'EA',
                'item' => ['name' => 'Additional Charge'],
            ])
            ->build();

        $this->assertStringContainsString('<cac:DebitNoteLine>', $xml);
        $this->assertStringContainsString('<cbc:DebitedQuantity unitCode="EA">3.0000</cbc:DebitedQuantity>', $xml);
    }

    public function test_validation_requires_original_invoice_id(): void
    {
        $this->expectException(\CamInv\EInvoice\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('originalInvoiceId');

        UBLBuilder::debitNote()
            ->setId('DN-001')
            ->setIssueDate('2026-05-06')
            ->setSupplier(['party_name' => 'Supplier'])
            ->setCustomer(['party_name' => 'Customer'])
            ->build();
    }
}
