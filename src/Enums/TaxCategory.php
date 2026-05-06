<?php

namespace CamInv\EInvoice\Enums;

enum TaxCategory: string
{
    case STANDARD = 'S';
    case ZERO_RATED = 'Z';
    case EXEMPT = 'E';

    public function label(): string
    {
        return match ($this) {
            self::STANDARD => 'Standard Rate',
            self::ZERO_RATED => 'Zero Rated',
            self::EXEMPT => 'Exempt',
        };
    }

    public function defaultRate(): float
    {
        return match ($this) {
            self::STANDARD => 10.00,
            self::ZERO_RATED => 0.00,
            self::EXEMPT => 0.00,
        };
    }
}
