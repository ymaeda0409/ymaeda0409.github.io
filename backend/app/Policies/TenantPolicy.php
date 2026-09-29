<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

/**
 * Two-step check shared by tenant-scoped policies:
 *   1. the record must be inside the user's tenant scope, otherwise 404 (existence is not leaked);
 *   2. the user's role must grant the permission, otherwise 403.
 */
abstract class TenantPolicy
{
    protected function allowsFor(User $user, Model $model, Permission $permission): Response
    {
        if (! $model->isVisibleTo($user)) {
            return Response::denyAsNotFound();
        }

        return $this->allows($user, $permission);
    }

    protected function allows(User $user, Permission $permission): Response
    {
        return $user->hasPermission($permission) ? Response::allow() : Response::deny();
    }
}
