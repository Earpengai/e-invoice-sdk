<?php

namespace CamInv\EInvoice\Tests\Unit\Exceptions;

use CamInv\EInvoice\Exceptions\CamInvException;
use CamInv\EInvoice\Tests\TestCase;

class CamInvExceptionTest extends TestCase
{
    public function test_constructor_and_getters(): void
    {
        $body = ['error' => 'something_wrong'];
        $e = new CamInvException('API error', 422, $body);

        $this->assertSame('API error', $e->getMessage());
        $this->assertSame(422, $e->getStatusCode());
        $this->assertSame($body, $e->getResponseBody());
    }

    public function test_defaults(): void
    {
        $e = new CamInvException('Default error');

        $this->assertSame('Default error', $e->getMessage());
        $this->assertSame(0, $e->getStatusCode());
        $this->assertNull($e->getResponseBody());
    }

    public function test_previous_exception(): void
    {
        $prev = new \RuntimeException('Previous');
        $e = new CamInvException('Wrapped', 500, null);

        $this->assertSame('Wrapped', $e->getMessage());
    }
}
