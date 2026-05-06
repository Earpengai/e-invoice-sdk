<?php

namespace CamInv\EInvoice\Tests\Unit\Exceptions;

use CamInv\EInvoice\Exceptions\ValidationException;
use CamInv\EInvoice\Tests\TestCase;

class ValidationExceptionTest extends TestCase
{
    public function test_invalid_ubl_xml(): void
    {
        $e = ValidationException::invalidUblXml('Missing required element');
        $this->assertStringContainsString('UBL XML validation failed', $e->getMessage());
        $this->assertStringContainsString('Missing required element', $e->getMessage());
        $this->assertSame(422, $e->getStatusCode());
    }

    public function test_missing_required_field(): void
    {
        $e = ValidationException::missingRequiredField('supplier');
        $this->assertSame('Missing required field: supplier', $e->getMessage());
        $this->assertSame(422, $e->getStatusCode());
    }

    public function test_invalid_document_status(): void
    {
        $e = ValidationException::invalidDocumentStatus('DRAFT', 'VALID');
        $this->assertStringContainsString("Invalid document status 'DRAFT'", $e->getMessage());
        $this->assertStringContainsString('Expected: VALID', $e->getMessage());
        $this->assertSame(422, $e->getStatusCode());
    }

    public function test_submission_failed(): void
    {
        $failed = [['index' => 1, 'error' => 'Invalid XML']];
        $e = ValidationException::submissionFailed($failed);
        $this->assertStringContainsString('Document submission partially failed', $e->getMessage());
        $this->assertStringContainsString('Invalid XML', $e->getMessage());
        $this->assertSame(422, $e->getStatusCode());
    }
}
