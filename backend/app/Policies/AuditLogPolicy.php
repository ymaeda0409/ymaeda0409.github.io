<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AuditLogPolicy extends TenantPolicy
{
    public function viewAny(User $user): Response
    {
        return $this->allows($user, Permission::AUDIT_LOGS_VIEW);
    }
}
