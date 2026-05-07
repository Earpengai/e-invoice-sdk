<?php

namespace CamInv\EInvoice\Tests\Unit\Enums;

use CamInv\EInvoice\Enums\TaxCategory;
use CamInv\EInvoice\Tests\TestCase;

class TaxCategoryTest extends TestCase
{
    public function test_vat(): void
    {
        $this->assertSame('VAT', TaxCategory::VAT->value);
        $this->assertSame('Value Added Tax', TaxCategory::VAT->label());
        $this->assertSame(10.00, TaxCategory::VAT->defaultRate());
    }

    public function test_specific_tax(): void
    {
        $this->assertSame('SP', TaxCategory::SPECIFIC_TAX->value);
        $this->assertSame('Specific Tax', TaxCategory::SPECIFIC_TAX->label());
        $this->assertSame(0.00, TaxCategory::SPECIFIC_TAX->defaultRate());
    }

    public function test_public_lighting_tax(): void
    {
        $this->assertSame('PLT', TaxCategory::PUBLIC_LIGHTING_TAX->value);
        $this->assertSame('Public Lighting Tax', TaxCategory::PUBLIC_LIGHTING_TAX->label());
        $this->assertSame(0.00, TaxCategory::PUBLIC_LIGHTING_TAX->defaultRate());
    }

    public function test_accommodation_tax(): void
    {
        $this->assertSame('AT', TaxCategory::ACCOMMODATION_TAX->value);
        $this->assertSame('Accommodation Tax', TaxCategory::ACCOMMODATION_TAX->label());
        $this->assertSame(0.00, TaxCategory::ACCOMMODATION_TAX->defaultRate());
    }
}
