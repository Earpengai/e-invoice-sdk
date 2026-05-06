<?php

namespace CamInv\EInvoice\UBL\Builders;

use CamInv\EInvoice\UBL\Elements;

class CreditNoteBuilder extends BaseBuilder
{
    protected ?string $originalInvoiceId = null;

    protected function getRootElement(): string
    {
        return 'CreditNote';
    }

    protected function getNamespace(): string
    {
        return 'urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2';
    }

    public function setOriginalInvoiceId(string $id): static
    {
        $this->originalInvoiceId = $id;

        return $this;
    }

    protected function buildBody(\DOMElement $root): void
    {
        if ($this->originalInvoiceId) {
            Elements\BillingReference::build($this->doc, $root, $this->originalInvoiceId);
        }

        parent::buildBody($root);
    }

    protected function buildLine(\DOMElement $root, array $data): void
    {
        Elements\CreditNoteLine::build($this->doc, $root, $data);
    }

    protected function validateRequiredFields(): void
    {
        parent::validateRequiredFields();

        if (empty($this->originalInvoiceId)) {
            throw new \CamInv\EInvoice\Exceptions\ValidationException(
                'Missing required field for Credit Note: originalInvoiceId',
                422,
            );
        }
    }
}
