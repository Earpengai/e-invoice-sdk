<?php

namespace CamInv\EInvoice\Support;

use CamInv\EInvoice\Exceptions\AuthenticationException;

trait HasTokenRefresh
{
    protected function withTokenRefresh(string $merchantId, callable $callback): mixed
    {
        try {
            return $callback();
        } catch (AuthenticationException $e) {
            if ($e->getStatusCode() !== 401) {
                throw $e;
            }

            if (method_exists($this, 'tokenManager') && method_exists($this, 'merchantId')) {
                $tokenManager = $this->tokenManager();
                $tokenManager->refreshAccessToken($merchantId);

                return $callback();
            }

            throw $e;
        }
    }
}
