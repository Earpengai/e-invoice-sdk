<?php

namespace CamInv\EInvoice\Tests\Unit\Enums;

use CamInv\EInvoice\Enums\DocumentDirection;
use CamInv\EInvoice\Tests\TestCase;

class DocumentDirectionTest extends TestCase
{
    public function test_sent(): void
    {
        $this->assertSame('SENT', DocumentDirection::SENT->value);
        $this->assertSame('Sent', DocumentDirection::SENT->label());
    }

    public function test_received(): void
    {
        $this->assertSame('RECEIVED', DocumentDirection::RECEIVED->value);
        $this->assertSame('Received', DocumentDirection::RECEIVED->label());
    }

    public function test_from(): void
    {
        $this->assertSame(DocumentDirection::SENT, DocumentDirection::from('SENT'));
        $this->assertSame(DocumentDirection::RECEIVED, DocumentDirection::from('RECEIVED'));
    }
}
