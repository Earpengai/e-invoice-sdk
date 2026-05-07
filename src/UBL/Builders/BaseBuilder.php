<?php

namespace CamInv\EInvoice\UBL\Builders;

use DOMDocument;

abstract class BaseBuilder
{
    protected DOMDocument $doc;

    protected string $currency;

    protected array $lines = [];

    protected array $taxData = [];

    protected ?string $id = null;

    protected ?string $issueDate = null;

    protected ?string $dueDate = null;

    protected ?string $note = null;

    protected ?string $invoiceTypeCode = null;

    protected ?array $supplier = null;

    protected ?array $customer = null;

    protected ?array $paymentTerms = null;

    protected ?array $additionalDocumentReferences = null;

    protected ?array $allowanceCharges = null;

    protected ?array $taxExchangeRate = null;

    protected ?array $monetaryTotal = null;

    public function __construct(array $options = [])
    {
        $this->doc = new DOMDocument('1.0', 'UTF-8');
        $this->doc->formatOutput = true;
        $this->currency = $options['currency'] ?? config('e-invoice.ubl.default_currency', 'KHR');
    }

    abstract protected function getRootElement(): string;

    abstract protected function getNamespace(): string;

    abstract protected function getMonetaryTotalElement(): string;

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function setIssueDate(string $date): static
    {
        $this->issueDate = $date;

        return $this;
    }

    public function setDueDate(?string $date): static
    {
        $this->dueDate = $date;

        return $this;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function setInvoiceTypeCode(string $code): static
    {
        $this->invoiceTypeCode = $code;

        return $this;
    }

    public function setDocumentCurrencyCode(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function setSupplier(array $data): static
    {
        $this->supplier = $data;

        return $this;
    }

    public function setCustomer(array $data): static
    {
        $this->customer = $data;

        return $this;
    }

    public function setPaymentTerms(array $data): static
    {
        $this->paymentTerms = $data;

        return $this;
    }

    public function setAdditionalDocumentReferences(array $refs): static
    {
        $this->additionalDocumentReferences = $refs;

        return $this;
    }

    public function setAllowanceCharges(array $items): static
    {
        $this->allowanceCharges = $items;

        return $this;
    }

    public function setTaxExchangeRate(array $data): static
    {
        $this->taxExchangeRate = $data;

        return $this;
    }

    public function setTaxTotal(array $taxSubtotals): static
    {
        $this->taxData = $taxSubtotals;

        return $this;
    }

    public function setMonetaryTotal(array $data): static
    {
        $this->monetaryTotal = $data;
        $this->monetaryTotal['currency'] = $data['currency'] ?? $this->currency;

        return $this;
    }

    public function addLine(array $data): static
    {
        $data['currency'] = $data['currency'] ?? $this->currency;
        $this->lines[] = $data;

        return $this;
    }

    public function addLines(array $lines): static
    {
        foreach ($lines as $line) {
            $this->addLine($line);
        }

        return $this;
    }

    public function build(): string
    {
        $this->validateRequiredFields();

        $root = $this->getRootElement();
        $ns = $this->getNamespace();

        $rootEl = $this->doc->createElementNS($ns, $root);
        $this->doc->appendChild($rootEl);

        $rootEl->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $rootEl->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

        $this->buildHeader($rootEl);
        $this->buildBody($rootEl);

        return $this->doc->saveXML();
    }

    protected function buildHeader(\DOMElement $root): void
    {
        $cbc = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';

        if (isset($this->id)) {
            $root->appendChild($this->doc->createElementNS($cbc, 'cbc:ID', $this->id));
        }

        if (isset($this->issueDate)) {
            $root->appendChild($this->doc->createElementNS($cbc, 'cbc:IssueDate', $this->issueDate));
        }

        if (isset($this->dueDate)) {
            $root->appendChild($this->doc->createElementNS($cbc, 'cbc:DueDate', $this->dueDate));
        }

        if (isset($this->invoiceTypeCode)) {
            $root->appendChild($this->doc->createElementNS($cbc, 'cbc:InvoiceTypeCode', $this->invoiceTypeCode));
        }

        if (isset($this->note)) {
            $root->appendChild($this->doc->createElementNS($cbc, 'cbc:Note', $this->note));
        }

        $root->appendChild($this->doc->createElementNS($cbc, 'cbc:DocumentCurrencyCode', $this->currency));
    }

    protected function buildBody(\DOMElement $root): void
    {
        if (isset($this->additionalDocumentReferences)) {
            \CamInv\EInvoice\UBL\Elements\AdditionalDocumentReference::build($this->doc, $root, $this->additionalDocumentReferences);
        }

        if (isset($this->supplier)) {
            \CamInv\EInvoice\UBL\Elements\SupplierParty::build($this->doc, $root, $this->supplier);
        }

        if (isset($this->customer)) {
            \CamInv\EInvoice\UBL\Elements\CustomerParty::build($this->doc, $root, $this->customer);
        }

        if (isset($this->paymentTerms)) {
            \CamInv\EInvoice\UBL\Elements\PaymentTerms::build($this->doc, $root, $this->paymentTerms);
        }

        if (isset($this->allowanceCharges)) {
            \CamInv\EInvoice\UBL\Elements\AllowanceCharge::build($this->doc, $root, $this->allowanceCharges);
        }

        if (isset($this->taxExchangeRate)) {
            \CamInv\EInvoice\UBL\Elements\TaxExchangeRate::build($this->doc, $root, $this->taxExchangeRate);
        }

        if (! empty($this->taxData)) {
            \CamInv\EInvoice\UBL\Elements\TaxTotal::build($this->doc, $root, $this->taxData);
        }

        if (isset($this->monetaryTotal)) {
            \CamInv\EInvoice\UBL\Elements\MonetaryTotal::build($this->doc, $root, $this->monetaryTotal, $this->getMonetaryTotalElement());
        }

        foreach ($this->lines as $line) {
            $this->buildLine($root, $line);
        }
    }

    abstract protected function buildLine(\DOMElement $root, array $data): void;

    protected function validateRequiredFields(): void
    {
        $missing = [];

        if (empty($this->id)) {
            $missing[] = 'id';
        }
        if (empty($this->issueDate)) {
            $missing[] = 'issueDate';
        }
        if (empty($this->supplier)) {
            $missing[] = 'supplier';
        }
        if (empty($this->customer)) {
            $missing[] = 'customer';
        }

        if (! empty($missing)) {
            throw new \CamInv\EInvoice\Exceptions\ValidationException(
                'Missing required fields for UBL document: ' . implode(', ', $missing),
                422,
            );
        }
    }
}
