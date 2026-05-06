<?php

namespace CamInv\EInvoice\Tests\Unit\Enums;

use CamInv\EInvoice\Enums\DocumentType;
use CamInv\EInvoice\Tests\TestCase;

class DocumentTypeTest extends TestCase
{
    public function test_invoice_values(): void
    {
        $this->assertSame('INVOICE', DocumentType::INVOICE->value);
        $this->assertSame('Invoice', DocumentType::INVOICE->label());
        $this->assertSame('380', DocumentType::INVOICE->ublCode());
        $this->assertSame(
            'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
            DocumentType::INVOICE->xmlns()
        );
    }

    public function test_credit_note_values(): void
    {
        $this->assertSame('CREDIT_NOTE', DocumentType::CREDIT_NOTE->value);
        $this->assertSame('Credit Note', DocumentType::CREDIT_NOTE->label());
        $this->assertSame('381', DocumentType::CREDIT_NOTE->ublCode());
        $this->assertSame(
            'urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2',
            DocumentType::CREDIT_NOTE->xmlns()
        );
    }

    public function test_debit_note_values(): void
    {
        $this->assertSame('DEBIT_NOTE', DocumentType::DEBIT_NOTE->value);
        $this->assertSame('Debit Note', DocumentType::DEBIT_NOTE->label());
        $this->assertSame('383', DocumentType::DEBIT_NOTE->ublCode());
        $this->assertSame(
            'urn:oasis:names:specification:ubl:schema:xsd:DebitNote-2',
            DocumentType::DEBIT_NOTE->xmlns()
        );
    }

    public function test_from_method(): void
    {
        $this->assertSame(DocumentType::INVOICE, DocumentType::from('INVOICE'));
        $this->assertSame(DocumentType::CREDIT_NOTE, DocumentType::from('CREDIT_NOTE'));
        $this->assertSame(DocumentType::DEBIT_NOTE, DocumentType::from('DEBIT_NOTE'));
    }
}
