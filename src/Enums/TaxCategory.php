<?php

namespace CamInv\EInvoice\Enums;

/**
 * Tax category codes for invoice line items.
 *
 * @see https://developer.e-invoice.gov.kh/data-type/tax-category
 */
enum TaxCategory: string
{
    case VAT = 'VAT';
    case SPECIFIC_TAX = 'SP';
    case PUBLIC_LIGHTING_TAX = 'PLT';
    case ACCOMMODATION_TAX = 'AT';

    public function label(): string
    {
        return match ($this) {
            self::VAT => 'Value Added Tax',
            self::SPECIFIC_TAX => 'Specific Tax',
            self::PUBLIC_LIGHTING_TAX => 'Public Lighting Tax',
            self::ACCOMMODATION_TAX => 'Accommodation Tax',
        };
    }

    public function defaultRate(): float
    {
        return match ($this) {
            self::VAT => 10.00,
            self::SPECIFIC_TAX => 0.00,
            self::PUBLIC_LIGHTING_TAX => 0.00,
            self::ACCOMMODATION_TAX => 0.00,
        };
    }
}
