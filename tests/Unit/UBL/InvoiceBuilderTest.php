<?php

namespace CamInv\EInvoice\Tests\Unit\UBL;

use CamInv\EInvoice\UBL\UBLBuilder;
use CamInv\EInvoice\Tests\TestCase;

class InvoiceBuilderTest extends TestCase
{
    protected function baseSupplier(): array
    {
        return [
            'endpoint_id' => 'KHUID00001234',
            'party_name' => 'Test Supplier Ltd.',
            'postal_address' => [
                'street_name' => '123 Main St',
                'city_name' => 'Phnom Penh',
                'country' => ['identification_code' => 'KH'],
            ],
            'party_tax_scheme' => [
                'company_id' => 'L001123456789',
                'tax_scheme_id' => 'VAT',
            ],
            'party_legal_entity' => [
                'registration_name' => 'Test Supplier Ltd.',
            ],
        ];
    }

    protected function baseCustomer(): array
    {
        return [
            'endpoint_id' => 'KHUID00005678',
            'party_name' => 'Test Customer Co.',
            'postal_address' => [
                'street_name' => '456 Another St',
                'city_name' => 'Siem Reap',
                'country' => ['identification_code' => 'KH'],
            ],
            'party_tax_scheme' => [
                'company_id' => 'L002987654321',
                'tax_scheme_id' => 'VAT',
            ],
            'party_legal_entity' => [
                'registration_name' => 'Test Customer Co.',
            ],
        ];
    }

    public function test_minimal_invoice(): void
    {
        $xml = UBLBuilder::invoice()
            ->setId('INV-001')
            ->setIssueDate('2026-05-06')
            ->setSupplier($this->baseSupplier())
            ->setCustomer($this->baseCustomer())
            ->build();

        $this->assertStringContainsString('<Invoice', $xml);
        $this->assertStringContainsString('xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"', $xml);
        $this->assertStringContainsString('<cbc:ID>INV-001</cbc:ID>', $xml);
        $this->assertStringContainsString('<cbc:IssueDate>2026-05-06</cbc:IssueDate>', $xml);
        $this->assertStringContainsString('<cbc:DocumentCurrencyCode>KHR</cbc:DocumentCurrencyCode>', $xml);
        $this->assertStringContainsString('KHUID00001234', $xml);
    }

    public function test_full_invoice_with_all_options(): void
    {
        $xml = UBLBuilder::invoice()
            ->setCustomizationId('urn:cen.eu:en16931:2017')
            ->setProfileId('urn:fdc:peppol.eu:2017:poacc:billing:01:1.0')
            ->setId('INV-FULL')
            ->setIssueDate('2026-05-06')
            ->setDueDate('2026-06-03')
            ->setNote('Payment due within 30 days')
            ->setInvoiceTypeCode('380')
            ->setDocumentCurrencyCode('KHR')
            ->setBuyerReference('PO-2026-001')
            ->setSupplier(array_merge($this->baseSupplier(), [
                'scheme_id' => 'KHM',
                'contact' => ['name' => 'John', 'telephone' => '012345678', 'email' => 'john@supplier.com'],
            ]))
            ->setCustomer($this->baseCustomer())
            ->setPaymentTerms(['note' => 'Net 30'])
            ->setTaxTotal([[
                'taxable_amount' => 100.00,
                'tax_amount' => 10.00,
                'tax_category_id' => 'S',
                'percent' => 10.00,
                'tax_scheme_id' => 'VAT',
            ]])
            ->setMonetaryTotal([
                'line_extension_amount' => 100.00,
                'tax_exclusive_amount' => 100.00,
                'tax_inclusive_amount' => 110.00,
                'payable_amount' => 110.00,
            ])
            ->addLine([
                'id' => '1',
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
                'tax_total' => [[
                    'taxable_amount' => 100.00,
                    'tax_amount' => 10.00,
                    'tax_category_id' => 'S',
                    'percent' => 10.00,
                ]],
            ])
            ->build();

        $this->assertStringContainsString('urn:cen.eu:en16931:2017', $xml);
        $this->assertStringContainsString('PO-2026-001', $xml);
        $this->assertStringContainsString('<cbc:DueDate>2026-06-03</cbc:DueDate>', $xml);
        $this->assertStringContainsString('<cbc:Note>Payment due within 30 days</cbc:Note>', $xml);
        $this->assertStringContainsString('<cbc:InvoiceTypeCode>380</cbc:InvoiceTypeCode>', $xml);
        $this->assertStringContainsString('<cac:PaymentTerms>', $xml);
        $this->assertStringContainsString('<cac:TaxTotal>', $xml);
        $this->assertStringContainsString('<cac:LegalMonetaryTotal>', $xml);
        $this->assertStringContainsString('<cac:InvoiceLine>', $xml);
        $this->assertStringContainsString('<cbc:InvoicedQuantity unitCode="EA">10.0000</cbc:InvoicedQuantity>', $xml);
        $this->assertStringContainsString('SKU-001', $xml);
        $this->assertStringContainsString('<cac:Contact>', $xml);
    }

    public function test_multiple_lines(): void
    {
        $xml = UBLBuilder::invoice()
            ->setId('INV-MULTI')
            ->setIssueDate('2026-05-06')
            ->setSupplier($this->baseSupplier())
            ->setCustomer($this->baseCustomer())
            ->addLine([
                'id' => '1',
                'item' => ['name' => 'Item A'],
            ])
            ->addLine([
                'id' => '2',
                'item' => ['name' => 'Item B'],
            ])
            ->addLine([
                'id' => '3',
                'item' => ['name' => 'Item C'],
            ])
            ->build();

        $count = substr_count($xml, '<cac:InvoiceLine>');
        $this->assertSame(3, $count);
    }

    public function test_validation_missing_id(): void
    {
        $this->expectException(\CamInv\EInvoice\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('Missing required fields');

        UBLBuilder::invoice()
            ->setIssueDate('2026-05-06')
            ->setSupplier($this->baseSupplier())
            ->setCustomer($this->baseCustomer())
            ->build();
    }

    public function test_validation_missing_supplier(): void
    {
        $this->expectException(\CamInv\EInvoice\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('Missing required fields');

        UBLBuilder::invoice()
            ->setId('INV-001')
            ->setIssueDate('2026-05-06')
            ->setCustomer($this->baseCustomer())
            ->build();
    }

    public function test_validation_missing_customer(): void
    {
        $this->expectException(\CamInv\EInvoice\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('Missing required fields');

        UBLBuilder::invoice()
            ->setId('INV-001')
            ->setIssueDate('2026-05-06')
            ->setSupplier($this->baseSupplier())
            ->build();
    }

    public function test_validation_missing_issue_date(): void
    {
        $this->expectException(\CamInv\EInvoice\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('Missing required fields');

        UBLBuilder::invoice()
            ->setId('INV-001')
            ->setSupplier($this->baseSupplier())
            ->setCustomer($this->baseCustomer())
            ->build();
    }
}
