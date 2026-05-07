<?php

namespace CamInv\EInvoice\Tests\Unit\UBL;

use CamInv\EInvoice\UBL\UBLBuilder;
use CamInv\EInvoice\Tests\TestCase;

class CreditNoteBuilderTest extends TestCase
{
    public function test_credit_note_includes_billing_reference(): void
    {
        $xml = UBLBuilder::creditNote()
            ->setId('CN-001')
            ->setIssueDate('2026-05-06')
            ->setNote('Wrong number of items')
            ->setOriginalInvoiceId('INV-001')
            ->setSupplier(['party_name' => 'Supplier'])
            ->setCustomer(['party_name' => 'Customer'])
            ->build();

        $this->assertStringContainsString('<CreditNote', $xml);
        $this->assertStringContainsString('xmlns="urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2"', $xml);
        $this->assertStringContainsString('<cac:BillingReference>', $xml);
        $this->assertStringContainsString('<cac:InvoiceDocumentReference>', $xml);
        $this->assertStringContainsString('<cbc:ID>INV-001</cbc:ID>', $xml);
    }

    public function test_credit_note_line_with_credited_quantity(): void
    {
        $xml = UBLBuilder::creditNote()
            ->setId('CN-002')
            ->setIssueDate('2026-05-06')
            ->setNote('Returned items')
            ->setOriginalInvoiceId('INV-001')
            ->setSupplier(['party_name' => 'Supplier'])
            ->setCustomer(['party_name' => 'Customer'])
            ->addLine([
                'id' => '1',
                'credited_quantity' => 5,
                'unit_code' => 'EA',
                'item' => ['name' => 'Returned Item'],
            ])
            ->build();

        $this->assertStringContainsString('<cac:CreditNoteLine>', $xml);
        $this->assertStringContainsString('<cbc:CreditedQuantity unitCode="EA">5.0000</cbc:CreditedQuantity>', $xml);
    }

    public function test_validation_requires_original_invoice_id(): void
    {
        $this->expectException(\CamInv\EInvoice\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('originalInvoiceId');

        UBLBuilder::creditNote()
            ->setId('CN-001')
            ->setIssueDate('2026-05-06')
            ->setNote('Test note')
            ->setSupplier(['party_name' => 'Supplier'])
            ->setCustomer(['party_name' => 'Customer'])
            ->build();
    }

    public function test_validation_requires_note(): void
    {
        $this->expectException(\CamInv\EInvoice\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('note');

        UBLBuilder::creditNote()
            ->setId('CN-001')
            ->setIssueDate('2026-05-06')
            ->setOriginalInvoiceId('INV-001')
            ->setSupplier(['party_name' => 'Supplier'])
            ->setCustomer(['party_name' => 'Customer'])
            ->build();
    }
}
