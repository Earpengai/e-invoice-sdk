<?php

namespace CamInv\EInvoice\Exceptions;

class ValidationException extends CamInvException
{
    public static function invalidUblXml(string $detail): self
    {
        return new self("UBL XML validation failed: {$detail}", 422);
    }

    public static function missingRequiredField(string $field): self
    {
        return new self("Missing required field: {$field}", 422);
    }

    public static function invalidDocumentStatus(string $current, string $expected): self
    {
        return new self("Invalid document status '{$current}' for this operation. Expected: {$expected}", 422);
    }

    public static function submissionFailed(array $failedDocuments): self
    {
        $details = json_encode($failedDocuments);

        return new self("Document submission partially failed: {$details}", 422);
    }
}
