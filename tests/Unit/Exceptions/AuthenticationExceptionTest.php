<?php

namespace CamInv\EInvoice\Tests\Unit\Exceptions;

use CamInv\EInvoice\Exceptions\AuthenticationException;
use CamInv\EInvoice\Tests\TestCase;

class AuthenticationExceptionTest extends TestCase
{
    public function test_invalid_credentials(): void
    {
        $e = AuthenticationException::invalidCredentials();
        $this->assertStringContainsString('Invalid client credentials', $e->getMessage());
        $this->assertSame(401, $e->getStatusCode());
    }

    public function test_token_expired(): void
    {
        $e = AuthenticationException::tokenExpired();
        $this->assertStringContainsString('Access token has expired', $e->getMessage());
        $this->assertSame(401, $e->getStatusCode());
    }

    public function test_invalid_state(): void
    {
        $e = AuthenticationException::invalidState();
        $this->assertStringContainsString('state parameter mismatch', $e->getMessage());
        $this->assertSame(403, $e->getStatusCode());
    }

    public function test_invalid_auth_token(): void
    {
        $e = AuthenticationException::invalidAuthToken();
        $this->assertStringContainsString('Invalid or expired authorization token', $e->getMessage());
        $this->assertSame(400, $e->getStatusCode());
    }
}
