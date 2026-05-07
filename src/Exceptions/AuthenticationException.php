<?php

namespace CamInv\EInvoice\Exceptions;

class AuthenticationException extends CamInvException
{
    public static function invalidCredentials(): self
    {
        return new self('Invalid client credentials. Check your CAMINV_CLIENT_ID and CAMINV_CLIENT_SECRET.', 401);
    }

    public static function tokenExpired(): self
    {
        return new self('Access token has expired and refresh failed. Re-authentication required.', 401);
    }

    public static function invalidState(): self
    {
        return new self('OAuth state parameter mismatch. Possible CSRF attack.', 403);
    }

    public static function invalidAuthToken(): self
    {
        return new self('Invalid or expired authorization token.', 400);
    }

    public static function revokeFailed(): self
    {
        return new self('Failed to revoke connected member.', 400);
    }
}
