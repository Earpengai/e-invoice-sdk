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

    protected function getMonetaryTotalElement(): string
    {
        return 'LegalMonetaryTotal';
    }

    public function setOriginalInvoiceId(string $id): static
    {
        $this->originalInvoiceId = $id;

        return $this;
    }

    protected function buildHeader(\DOMElement $root): void
    {
        parent::buildHeader($root);

        if ($this->originalInvoiceId) {
            Elements\BillingReference::build($this->doc, $root, $this->originalInvoiceId);
        }
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

        if (empty($this->note)) {
            throw new \CamInv\EInvoice\Exceptions\ValidationException(
                'Missing required field for Credit Note: note',
                422,
            );
        }
    }

    protected function buildBody(\DOMElement $root): void
    {
        if (isset($this->additionalDocumentReferences)) {
            Elements\AdditionalDocumentReference::build($this->doc, $root, $this->additionalDocumentReferences);
        }

        if (isset($this->supplier)) {
            Elements\SupplierParty::build($this->doc, $root, $this->supplier);
        }

        if (isset($this->customer)) {
            Elements\CustomerParty::build($this->doc, $root, $this->customer);
        }

        if (isset($this->paymentTerms)) {
            Elements\PaymentTerms::build($this->doc, $root, $this->paymentTerms);
        }

        if (isset($this->taxExchangeRate)) {
            Elements\TaxExchangeRate::build($this->doc, $root, $this->taxExchangeRate);
        }

        if (isset($this->allowanceCharges)) {
            Elements\AllowanceCharge::build($this->doc, $root, $this->allowanceCharges);
        }

        if (! empty($this->taxData)) {
            Elements\TaxTotal::build($this->doc, $root, $this->taxData);
        }

        if (isset($this->monetaryTotal)) {
            Elements\MonetaryTotal::build($this->doc, $root, $this->monetaryTotal, $this->getMonetaryTotalElement());
        }

        foreach ($this->lines as $line) {
            $this->buildLine($root, $line);
        }
    }
}
