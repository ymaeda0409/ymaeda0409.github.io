<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\DeliveryZone;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DeliveryZonePolicy extends TenantPolicy
{
    public function viewAny(User $user): Response
    {
        return $this->allows($user, Permission::DELIVERY_ZONES_VIEW);
    }

    public function view(User $user, DeliveryZone $zone): Response
    {
        return $this->allowsFor($user, $zone, Permission::DELIVERY_ZONES_VIEW);
    }

    public function create(User $user): Response
    {
        return $this->allows($user, Permission::DELIVERY_ZONES_MANAGE);
    }

    public function update(User $user, DeliveryZone $zone): Response
    {
        return $this->allowsFor($user, $zone, Permission::DELIVERY_ZONES_MANAGE);
    }

    public function delete(User $user, DeliveryZone $zone): Response
    {
        return $this->allowsFor($user, $zone, Permission::DELIVERY_ZONES_MANAGE);
    }
}
