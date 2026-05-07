<?php

namespace CamInv\EInvoice\Support;

use CamInv\EInvoice\Exceptions\AuthenticationException;

/**
 * Provides automatic token refresh on 401 responses for API clients.
 *
 * When wrapped around a callback that makes an API call with a bearer
 * token, a 401 response triggers a token refresh and a single retry.
 *
 * The consuming class must implement:
 *   - tokenManager(): \CamInv\EInvoice\Token\TokenManager
 *   - merchantId(): string
 */
trait HasTokenRefresh
{
    /**
     * Execute a callback and retry once with a refreshed token on 401.
     *
     * @param  callable  $callback  API call to execute (should re-resolve the token internally).
     * @return mixed                The callback's return value.
     *
     * @throws \CamInv\EInvoice\Exceptions\AuthenticationException
     * @throws \RuntimeException    If the consuming class lacks tokenManager() or merchantId().
     */
    protected function withTokenRefresh(callable $callback): mixed
    {
        if (! method_exists($this, 'tokenManager') || ! method_exists($this, 'merchantId')) {
            throw new \RuntimeException(
                'HasTokenRefresh requires tokenManager() and merchantId() methods on the consuming class.'
            );
        }

        try {
            return $callback();
        } catch (AuthenticationException $e) {
            if ($e->getStatusCode() !== 401) {
                throw $e;
            }

            $this->tokenManager()->refreshAccessToken($this->merchantId());

            return $callback();
        }
    }
}
