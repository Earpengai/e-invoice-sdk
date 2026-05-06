<?php

namespace CamInv\EInvoice\Tests\Unit\UBL;

use CamInv\EInvoice\UBL\UBLBuilder;
use CamInv\EInvoice\Tests\TestCase;

class UBLBuilderTest extends TestCase
{
    public function test_factory_returns_invoice_builder(): void
    {
        $builder = UBLBuilder::invoice();

        $this->assertInstanceOf(\CamInv\EInvoice\UBL\Builders\InvoiceBuilder::class, $builder);
    }

    public function test_factory_returns_credit_note_builder(): void
    {
        $builder = UBLBuilder::creditNote();

        $this->assertInstanceOf(\CamInv\EInvoice\UBL\Builders\CreditNoteBuilder::class, $builder);
    }

    public function test_factory_returns_debit_note_builder(): void
    {
        $builder = UBLBuilder::debitNote();

        $this->assertInstanceOf(\CamInv\EInvoice\UBL\Builders\DebitNoteBuilder::class, $builder);
    }

    public function test_factory_passes_options(): void
    {
        $builder = UBLBuilder::invoice(['currency' => 'USD']);

        $xml = $builder
            ->setId('INV-001')
            ->setIssueDate('2026-05-06')
            ->setSupplier(['party_name' => 'Supplier'])
            ->setCustomer(['party_name' => 'Customer'])
            ->build();

        $this->assertStringContainsString('DocumentCurrencyCode', $xml);
        $this->assertStringContainsString('USD', $xml);
    }
}
