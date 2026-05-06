<?php

namespace CamInv\EInvoice\Token;

use CamInv\EInvoice\Auth\OAuthService;
use CamInv\EInvoice\Contracts\TokenStore;
use CamInv\EInvoice\Exceptions\TokenExpiredException;
use CamInv\EInvoice\Support\Config;

class TokenManager
{
    public function __construct(
        protected TokenStore $store,
        protected OAuthService $authService,
        protected Config $config,
    ) {}

    public function getValidAccessToken(string $merchantId): string
    {
        $token = $this->store->get($merchantId);

        if (! $token) {
            throw TokenExpiredException::noTokenStored();
        }

        if ($this->isTokenExpired($token)) {
            $token = $this->refreshAccessToken($merchantId);
        }

        return $token['access_token'];
    }

    public function isTokenExpired(array $token): bool
    {
        if (! isset($token['expires_at'])) {
            return true;
        }

        $buffer = $this->config->tokenRefreshBufferMinutes() * 60;
        $expiresAt = is_numeric($token['expires_at'])
            ? (int) $token['expires_at']
            : strtotime($token['expires_at']);

        return (time() + $buffer) >= $expiresAt;
    }

    public function refreshAccessToken(string $merchantId): array
    {
        $token = $this->store->get($merchantId);

        if (! $token || empty($token['refresh_token'])) {
            throw TokenExpiredException::expiredAndCannotRefresh();
        }

        $response = $this->authService->refreshAccessToken($token['refresh_token']);

        $this->store->put($merchantId, $response);

        return $response;
    }

    public function refreshExpiringTokens(): array
    {
        $buffer = $this->config->tokenRefreshBufferMinutes() * 60;
        $expiring = $this->store->expiringWithin($buffer);

        $results = [];

        foreach ($expiring as $item) {
            $merchantId = $item['merchant_id'] ?? $item['id'] ?? null;
            if (! $merchantId) {
                continue;
            }

            try {
                $results[$merchantId] = $this->refreshAccessToken($merchantId);
            } catch (\Exception $e) {
                $results[$merchantId] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    public function calculateExpiresAt(int $expiresIn): int
    {
        return time() + $expiresIn;
    }
}
