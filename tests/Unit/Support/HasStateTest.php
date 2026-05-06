<?php

namespace CamInv\EInvoice\Tests\Unit\Support;

use CamInv\EInvoice\Support\HasState;
use CamInv\EInvoice\Tests\TestCase;

class HasStateTest extends TestCase
{
    use HasState;

    public function test_generate_state_returns_40_char_hex_string(): void
    {
        $state = $this->generateState();

        $this->assertIsString($state);
        $this->assertSame(40, strlen($state));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{40}$/', $state);
    }

    public function test_generate_state_is_unique(): void
    {
        $a = $this->generateState();
        $b = $this->generateState();

        $this->assertNotSame($a, $b);
    }

    public function test_validate_state_matching(): void
    {
        $state = $this->generateState();

        $this->assertTrue($this->validateState($state, $state));
    }

    public function test_validate_state_mismatch(): void
    {
        $this->assertFalse($this->validateState('abc', 'def'));
    }

    public function test_validate_state_different_length(): void
    {
        $this->assertFalse($this->validateState('short', 'longerstring'));
    }
}
