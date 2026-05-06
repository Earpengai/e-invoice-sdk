<?php

namespace CamInv\EInvoice\Exceptions;

class TokenExpiredException extends CamInvException
{
    public static function expiredAndCannotRefresh(): self
    {
        return new self('Access token has expired and could not be refreshed.', 401);
    }

    public static function noTokenStored(): self
    {
        return new self('No tokens stored for this merchant. Please reconnect your CamInv account.', 401);
    }
}
