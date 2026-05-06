<?php

namespace CamInv\EInvoice\Tests\Unit\Enums;

use CamInv\EInvoice\Enums\RegistrationStatus;
use CamInv\EInvoice\Tests\TestCase;

class RegistrationStatusTest extends TestCase
{
    public function test_labels(): void
    {
        $this->assertSame('Pending', RegistrationStatus::PENDING->label());
        $this->assertSame('Connected', RegistrationStatus::CONNECTED->label());
        $this->assertSame('Revoked', RegistrationStatus::REVOKED->label());
        $this->assertSame('Expired', RegistrationStatus::EXPIRED->label());
    }

    public function test_colors(): void
    {
        $this->assertSame('yellow', RegistrationStatus::PENDING->color());
        $this->assertSame('green', RegistrationStatus::CONNECTED->color());
        $this->assertSame('red', RegistrationStatus::REVOKED->color());
        $this->assertSame('gray', RegistrationStatus::EXPIRED->color());
    }

    public function test_values(): void
    {
        $this->assertSame('pending', RegistrationStatus::PENDING->value);
        $this->assertSame('connected', RegistrationStatus::CONNECTED->value);
        $this->assertSame('revoked', RegistrationStatus::REVOKED->value);
        $this->assertSame('expired', RegistrationStatus::EXPIRED->value);
    }
}
