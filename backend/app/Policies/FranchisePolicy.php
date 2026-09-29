<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Franchise;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FranchisePolicy extends TenantPolicy
{
    public function viewAny(User $user): Response
    {
        return $this->allows($user, Permission::FRANCHISES_VIEW);
    }

    public function view(User $user, Franchise $franchise): Response
    {
        return $this->allowsFor($user, $franchise, Permission::FRANCHISES_VIEW);
    }

    public function create(User $user): Response
    {
        return $this->allows($user, Permission::FRANCHISES_MANAGE);
    }

    public function update(User $user, Franchise $franchise): Response
    {
        return $this->allowsFor($user, $franchise, Permission::FRANCHISES_MANAGE);
    }

    public function delete(User $user, Franchise $franchise): Response
    {
        return $this->allowsFor($user, $franchise, Permission::FRANCHISES_MANAGE);
    }
}
