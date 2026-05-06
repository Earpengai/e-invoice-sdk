<?php

namespace CamInv\EInvoice\Exceptions;

class ConnectionException extends CamInvException
{
    public static function timeout(string $url, int $seconds): self
    {
        return new self("Connection to CamInv timed out after {$seconds} seconds: {$url}", 0);
    }

    public static function networkError(string $url, string $error): self
    {
        return new self("Network error connecting to CamInv ({$url}): {$error}", 0);
    }

    public static function sslError(string $url, string $error): self
    {
        return new self("SSL error connecting to CamInv ({$url}): {$error}", 0);
    }
}
