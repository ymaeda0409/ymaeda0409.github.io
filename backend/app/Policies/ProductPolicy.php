<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductPolicy extends TenantPolicy
{
    public function viewAny(User $user): Response
    {
        return $this->allows($user, Permission::CATALOG_VIEW);
    }

    public function view(User $user, Product $product): Response
    {
        return $this->allowsFor($user, $product, Permission::CATALOG_VIEW);
    }

    public function create(User $user): Response
    {
        return $this->allows($user, Permission::CATALOG_MANAGE);
    }

    public function update(User $user, Product $product): Response
    {
        return $this->allowsFor($user, $product, Permission::CATALOG_MANAGE);
    }

    public function delete(User $user, Product $product): Response
    {
        return $this->allowsFor($user, $product, Permission::CATALOG_MANAGE);
    }
}
