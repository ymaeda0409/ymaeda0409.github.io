<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DriverPolicy extends TenantPolicy
{
    public function viewAny(User $user): Response
    {
        return $this->allows($user, Permission::DRIVERS_MANAGE);
    }

    public function view(User $user, Driver $driver): Response
    {
        return $this->allowsFor($user, $driver, Permission::DRIVERS_MANAGE);
    }

    public function create(User $user): Response
    {
        return $this->allows($user, Permission::DRIVERS_MANAGE);
    }

    public function update(User $user, Driver $driver): Response
    {
        return $this->allowsFor($user, $driver, Permission::DRIVERS_MANAGE);
    }
}
