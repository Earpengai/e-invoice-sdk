<?php

namespace CamInv\EInvoice\UBL\Builders;

use CamInv\EInvoice\UBL\Elements;

/**
 * Builds a UBL Invoice XML document.
 */
class InvoiceBuilder extends BaseBuilder
{
    protected function getRootElement(): string
    {
        return 'Invoice';
    }

    protected function getNamespace(): string
    {
        return 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2';
    }

    protected function getMonetaryTotalElement(): string
    {
        return 'LegalMonetaryTotal';
    }

    protected function buildLine(\DOMElement $root, array $data): void
    {
        Elements\InvoiceLine::build($this->doc, $root, $data);
    }
}
