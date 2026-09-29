<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductCategoryPolicy extends TenantPolicy
{
    public function viewAny(User $user): Response
    {
        return $this->allows($user, Permission::CATALOG_VIEW);
    }

    public function view(User $user, ProductCategory $category): Response
    {
        return $this->allowsFor($user, $category, Permission::CATALOG_VIEW);
    }

    public function create(User $user): Response
    {
        return $this->allows($user, Permission::CATALOG_MANAGE);
    }

    public function update(User $user, ProductCategory $category): Response
    {
        return $this->allowsFor($user, $category, Permission::CATALOG_MANAGE);
    }

    public function delete(User $user, ProductCategory $category): Response
    {
        return $this->allowsFor($user, $category, Permission::CATALOG_MANAGE);
    }
}
