<?php

namespace CamInv\EInvoice\Tests\Unit\Exceptions;

use CamInv\EInvoice\Exceptions\TokenExpiredException;
use CamInv\EInvoice\Tests\TestCase;

class TokenExpiredExceptionTest extends TestCase
{
    public function test_expired_and_cannot_refresh(): void
    {
        $e = TokenExpiredException::expiredAndCannotRefresh();
        $this->assertStringContainsString('expired and could not be refreshed', $e->getMessage());
        $this->assertSame(401, $e->getStatusCode());
    }

    public function test_no_token_stored(): void
    {
        $e = TokenExpiredException::noTokenStored();
        $this->assertStringContainsString('No tokens stored', $e->getMessage());
        $this->assertSame(401, $e->getStatusCode());
    }
}
