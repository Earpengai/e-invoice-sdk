<?php

namespace CamInv\EInvoice\Enums;

enum DocumentStatus: string
{
    case DRAFT = 'DRAFT';
    case SUBMITTING = 'SUBMITTING';
    case VALID = 'VALID';
    case DELIVERED = 'DELIVERED';
    case ACKNOWLEDGED = 'ACKNOWLEDGED';
    case IN_PROCESS = 'IN_PROCESS';
    case UNDER_QUERY = 'UNDER_QUERY';
    case CONDITIONALLY_ACCEPTED = 'CONDITIONALLY_ACCEPTED';
    case ACCEPTED = 'ACCEPTED';
    case REJECTED = 'REJECTED';
    case PAID = 'PAID';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTING => 'Submitting',
            self::VALID => 'Valid',
            self::DELIVERED => 'Delivered',
            self::ACKNOWLEDGED => 'Acknowledged',
            self::IN_PROCESS => 'In Process',
            self::UNDER_QUERY => 'Under Query',
            self::CONDITIONALLY_ACCEPTED => 'Conditionally Accepted',
            self::ACCEPTED => 'Accepted',
            self::REJECTED => 'Rejected',
            self::PAID => 'Paid',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SUBMITTING => 'blue',
            self::VALID => 'cyan',
            self::DELIVERED => 'teal',
            self::ACKNOWLEDGED => 'indigo',
            self::IN_PROCESS => 'orange',
            self::UNDER_QUERY => 'yellow',
            self::CONDITIONALLY_ACCEPTED => 'lime',
            self::ACCEPTED => 'green',
            self::REJECTED => 'red',
            self::PAID => 'emerald',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::ACCEPTED,
            self::REJECTED,
            self::PAID,
        ], true);
    }
}
