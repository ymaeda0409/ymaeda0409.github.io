<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Kitchen;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class KitchenPolicy extends TenantPolicy
{
    public function viewAny(User $user): Response
    {
        return $this->allows($user, Permission::KITCHENS_VIEW);
    }

    public function view(User $user, Kitchen $kitchen): Response
    {
        return $this->allowsFor($user, $kitchen, Permission::KITCHENS_VIEW);
    }

    public function create(User $user): Response
    {
        return $this->allows($user, Permission::KITCHENS_MANAGE);
    }

    public function update(User $user, Kitchen $kitchen): Response
    {
        return $this->allowsFor($user, $kitchen, Permission::KITCHENS_MANAGE);
    }

    public function delete(User $user, Kitchen $kitchen): Response
    {
        return $this->allowsFor($user, $kitchen, Permission::KITCHENS_MANAGE);
    }
}
