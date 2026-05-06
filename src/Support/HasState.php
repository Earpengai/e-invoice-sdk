<?php

namespace CamInv\EInvoice\Support;

trait HasState
{
    public function generateState(): string
    {
        return bin2hex(random_bytes(20));
    }

    public function validateState(string $received, string $stored): bool
    {
        return hash_equals($stored, $received);
    }
}
