<?php

namespace CamInv\EInvoice\Enums;

/**
 * Registration status for a connected business member.
 */
enum RegistrationStatus: string
{
    case PENDING = 'pending';
    case CONNECTED = 'connected';
    case REVOKED = 'revoked';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::CONNECTED => 'Connected',
            self::REVOKED => 'Revoked',
            self::EXPIRED => 'Expired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::CONNECTED => 'green',
            self::REVOKED => 'red',
            self::EXPIRED => 'gray',
        };
    }
}
