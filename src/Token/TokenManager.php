<?php

namespace CamInv\EInvoice\Token;

use CamInv\EInvoice\Auth\OAuthService;
use CamInv\EInvoice\Contracts\TokenStore;
use CamInv\EInvoice\Exceptions\TokenExpiredException;
use CamInv\EInvoice\Support\Config;

/**
 * Manages access token lifecycle: retrieval, expiry check, refresh, and batch refresh.
 *
 * Uses a pluggable TokenStore for persistence and an OAuthService
 * for communicating with the CamInv authentication API.
 */
class TokenManager
{
    /**
     * @param  TokenStore    $store        Persistent storage for tokens grouped by merchant.
     * @param  OAuthService  $authService  OAuth client used to refresh expired tokens.
     * @param  Config        $config       SDK configuration (buffer, secrets, etc.).
     */
    public function __construct(
        protected TokenStore $store,
        protected OAuthService $authService,
        protected Config $config,
    ) {}

    /**
     * Return a valid (non‑expired) access token for the given merchant.
     *
     * If the stored token has already expired (accounting for the configured
     * refresh buffer), a new token is obtained via the OAuth refresh flow
     * before returning.
     *
     * @param  string  $merchantId  The merchant identifier used as the storage key.
     * @return string               A usable access token.
     *
     * @throws \CamInv\EInvoice\Exceptions\TokenExpiredException
     */
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

    /**
     * Determine whether a token payload is expired, counting the refresh buffer.
     *
     * A token without an `expires_at` key is always considered expired.
     * The buffer (in minutes, converted to seconds) causes proactive refresh
     * before the absolute expiry time is reached.
     *
     * @param  array  $token  Token payload (must contain `expires_at`).
     * @return bool
     */
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

    /**
     * Refresh the access token for a merchant using its stored refresh token.
     *
     * The `expires_at` timestamp is computed from the API response's
     * `expire_in` (or `expires_in`) value so that future calls to
     * {@see isTokenExpired()} work correctly.
     *
     * @param  string  $merchantId  The merchant whose token should be refreshed.
     * @return array                The complete token payload (including `expires_at`).
     *
     * @throws \CamInv\EInvoice\Exceptions\TokenExpiredException
     */
    public function refreshAccessToken(string $merchantId): array
    {
        $token = $this->store->get($merchantId);

        if (! $token || empty($token['refresh_token'])) {
            throw TokenExpiredException::expiredAndCannotRefresh();
        }

        $response = $this->authService->refreshAccessToken($token['refresh_token']);

        if (empty($response['refresh_token'])) {
            $response['refresh_token'] = $token['refresh_token'];
        }

        if (! isset($response['expires_at'])) {
            $expiresIn = (int) ($response['expire_in'] ?? $response['expires_in'] ?? 0);
            $response['expires_at'] = $this->calculateExpiresAt($expiresIn);
        }

        $this->store->put($merchantId, $response);

        return $response;
    }

    /**
     * Refresh all tokens that will expire within the configured buffer window.
     *
     * Queries the {@see TokenStore} for tokens expiring soon and attempts to
     * refresh each one. Errors for individual merchants are captured as
     * `['error' => $message]` entries instead of aborting the whole batch.
     *
     * @return array<string, array>  Map of merchant ID to the refreshed token
     *                               payload or an `['error' => ...]` array.
     */
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

    /**
     * Calculate the absolute Unix timestamp at which a token expires.
     *
     * @param  int  $expiresIn  Seconds until the token expires.
     * @return int               Unix timestamp of the expiry moment.
     */
    public function calculateExpiresAt(int $expiresIn): int
    {
        return time() + $expiresIn;
    }
}
