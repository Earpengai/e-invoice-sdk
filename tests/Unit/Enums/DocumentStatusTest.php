<?php

namespace CamInv\EInvoice\Tests\Unit\Enums;

use CamInv\EInvoice\Enums\DocumentStatus;
use CamInv\EInvoice\Tests\TestCase;

class DocumentStatusTest extends TestCase
{
    public function test_terminal_statuses(): void
    {
        $this->assertFalse(DocumentStatus::DRAFT->isTerminal());
        $this->assertFalse(DocumentStatus::SUBMITTING->isTerminal());
        $this->assertFalse(DocumentStatus::VALID->isTerminal());
        $this->assertFalse(DocumentStatus::DELIVERED->isTerminal());
        $this->assertFalse(DocumentStatus::ACKNOWLEDGED->isTerminal());
        $this->assertFalse(DocumentStatus::IN_PROCESS->isTerminal());
        $this->assertFalse(DocumentStatus::UNDER_QUERY->isTerminal());
        $this->assertFalse(DocumentStatus::CONDITIONALLY_ACCEPTED->isTerminal());
        $this->assertTrue(DocumentStatus::ACCEPTED->isTerminal());
        $this->assertTrue(DocumentStatus::REJECTED->isTerminal());
        $this->assertTrue(DocumentStatus::PAID->isTerminal());
    }

    public function test_labels(): void
    {
        $this->assertSame('Draft', DocumentStatus::DRAFT->label());
        $this->assertSame('Submitting', DocumentStatus::SUBMITTING->label());
        $this->assertSame('Valid', DocumentStatus::VALID->label());
        $this->assertSame('Delivered', DocumentStatus::DELIVERED->label());
        $this->assertSame('Accepted', DocumentStatus::ACCEPTED->label());
        $this->assertSame('Rejected', DocumentStatus::REJECTED->label());
        $this->assertSame('Paid', DocumentStatus::PAID->label());
    }

    public function test_colors(): void
    {
        $this->assertSame('gray', DocumentStatus::DRAFT->color());
        $this->assertSame('blue', DocumentStatus::SUBMITTING->color());
        $this->assertSame('green', DocumentStatus::ACCEPTED->color());
        $this->assertSame('red', DocumentStatus::REJECTED->color());
        $this->assertSame('emerald', DocumentStatus::PAID->color());
        $this->assertSame('orange', DocumentStatus::IN_PROCESS->color());
    }

    public function test_values(): void
    {
        $this->assertSame('DRAFT', DocumentStatus::DRAFT->value);
        $this->assertSame('SUBMITTING', DocumentStatus::SUBMITTING->value);
        $this->assertSame('ACCEPTED', DocumentStatus::ACCEPTED->value);
        $this->assertSame('REJECTED', DocumentStatus::REJECTED->value);
    }
}
