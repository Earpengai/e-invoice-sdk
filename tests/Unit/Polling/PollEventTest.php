<?php

namespace CamInv\EInvoice\Tests\Unit\Polling;

use CamInv\EInvoice\Polling\PollEvent;
use CamInv\EInvoice\Tests\TestCase;

class PollEventTest extends TestCase
{
    public function test_parse_send_event(): void
    {
        $payload = [
            'document_id' => 'uuid-123',
            'updated_at' => '2024-06-01T01:00:00',
            'type' => 'SEND',
        ];

        $event = PollEvent::fromArray($payload);

        $this->assertSame('uuid-123', $event->documentId);
        $this->assertSame('2024-06-01T01:00:00', $event->updatedAt);
        $this->assertSame('SEND', $event->type);
        $this->assertTrue($event->isSend());
        $this->assertFalse($event->isReceive());
    }

    public function test_parse_receive_event(): void
    {
        $payload = [
            'document_id' => 'uuid-456',
            'updated_at' => '2024-06-02T02:00:00',
            'type' => 'RECEIVE',
        ];

        $event = PollEvent::fromArray($payload);

        $this->assertSame('uuid-456', $event->documentId);
        $this->assertSame('2024-06-02T02:00:00', $event->updatedAt);
        $this->assertSame('RECEIVE', $event->type);
        $this->assertTrue($event->isReceive());
        $this->assertFalse($event->isSend());
    }

    public function test_stores_full_payload(): void
    {
        $payload = ['document_id' => 'u-1', 'updated_at' => '2024-01-01T00:00:00', 'type' => 'SEND', 'extra' => 'data'];
        $event = PollEvent::fromArray($payload);

        $this->assertSame($payload, $event->payload);
    }
}
