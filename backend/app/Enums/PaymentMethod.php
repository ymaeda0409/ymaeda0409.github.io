<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'CASH';
    case AIRTEL_MONEY = 'AIRTEL_MONEY';
    case TNM_MPAMBA = 'TNM_MPAMBA';

    public function isPrepaid(): bool
    {
        return $this !== self::CASH;
    }
}
