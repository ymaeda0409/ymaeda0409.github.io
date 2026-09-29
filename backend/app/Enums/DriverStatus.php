<?php

namespace App\Enums;

/** Employment status (managed by staff); availability is `drivers.is_online`. */
enum DriverStatus: string
{
    case ACTIVE = 'ACTIVE';
    case SUSPENDED = 'SUSPENDED';
}
