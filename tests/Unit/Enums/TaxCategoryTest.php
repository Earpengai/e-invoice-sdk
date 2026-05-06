<?php

namespace CamInv\EInvoice\Tests\Unit\Enums;

use CamInv\EInvoice\Enums\TaxCategory;
use CamInv\EInvoice\Tests\TestCase;

class TaxCategoryTest extends TestCase
{
    public function test_standard_rate(): void
    {
        $this->assertSame('S', TaxCategory::STANDARD->value);
        $this->assertSame('Standard Rate', TaxCategory::STANDARD->label());
        $this->assertSame(10.00, TaxCategory::STANDARD->defaultRate());
    }

    public function test_zero_rated(): void
    {
        $this->assertSame('Z', TaxCategory::ZERO_RATED->value);
        $this->assertSame('Zero Rated', TaxCategory::ZERO_RATED->label());
        $this->assertSame(0.00, TaxCategory::ZERO_RATED->defaultRate());
    }

    public function test_exempt(): void
    {
        $this->assertSame('E', TaxCategory::EXEMPT->value);
        $this->assertSame('Exempt', TaxCategory::EXEMPT->label());
        $this->assertSame(0.00, TaxCategory::EXEMPT->defaultRate());
    }
}
