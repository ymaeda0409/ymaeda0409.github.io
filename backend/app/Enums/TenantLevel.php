<?php

namespace App\Enums;

enum TenantLevel: string
{
    case FRANCHISE = 'franchise';
    case STORE = 'store';
    /** Role has no access to tenant-scoped back-office data. */
    case NONE = 'none';
}
