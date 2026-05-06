<?php

namespace CamInv\EInvoice\Tests\Unit\Exceptions;

use CamInv\EInvoice\Exceptions\ConnectionException;
use CamInv\EInvoice\Tests\TestCase;

class ConnectionExceptionTest extends TestCase
{
    public function test_timeout(): void
    {
        $e = ConnectionException::timeout('https://api.example.com/endpoint', 30);
        $this->assertStringContainsString('timed out after 30 seconds', $e->getMessage());
        $this->assertStringContainsString('https://api.example.com/endpoint', $e->getMessage());
        $this->assertSame(0, $e->getStatusCode());
    }

    public function test_network_error(): void
    {
        $e = ConnectionException::networkError('https://api.example.com', 'DNS resolution failed');
        $this->assertStringContainsString('Network error', $e->getMessage());
        $this->assertStringContainsString('DNS resolution failed', $e->getMessage());
    }

    public function test_ssl_error(): void
    {
        $e = ConnectionException::sslError('https://api.example.com', 'Certificate expired');
        $this->assertStringContainsString('SSL error', $e->getMessage());
        $this->assertStringContainsString('Certificate expired', $e->getMessage());
    }
}
