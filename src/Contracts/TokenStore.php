<?php

namespace CamInv\EInvoice\Contracts;

interface TokenStore
{
    public function get(string $merchantId): ?array;

    public function put(string $merchantId, array $tokenResponse): void;

    public function forget(string $merchantId): void;

    public function expiringWithin(int $seconds): array;
}
