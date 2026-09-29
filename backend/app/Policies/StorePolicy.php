<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Store;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StorePolicy extends TenantPolicy
{
    public function viewAny(User $user): Response
    {
        return $this->allows($user, Permission::STORES_VIEW);
    }

    public function view(User $user, Store $store): Response
    {
        return $this->allowsFor($user, $store, Permission::STORES_VIEW);
    }

    public function create(User $user): Response
    {
        return $this->allows($user, Permission::STORES_CREATE);
    }

    public function update(User $user, Store $store): Response
    {
        return $this->allowsFor($user, $store, Permission::STORES_UPDATE);
    }

    public function delete(User $user, Store $store): Response
    {
        return $this->allowsFor($user, $store, Permission::STORES_CREATE);
    }

    public function manageProducts(User $user, Store $store): Response
    {
        return $this->allowsFor($user, $store, Permission::STORE_PRODUCTS_MANAGE);
    }
}
