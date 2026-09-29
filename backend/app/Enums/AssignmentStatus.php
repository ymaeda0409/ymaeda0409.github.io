<?php

namespace App\Enums;

enum AssignmentStatus: string
{
    case OFFERED = 'OFFERED';
    case ACCEPTED = 'ACCEPTED';
    case DECLINED = 'DECLINED';
    case EXPIRED = 'EXPIRED';
    /** Offer withdrawn because the order was cancelled or taken by someone else. */
    case WITHDRAWN = 'WITHDRAWN';
}
