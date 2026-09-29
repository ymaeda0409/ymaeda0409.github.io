<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Back-office access to orders. Customers reach their own orders through a
 * customer-scoped query instead (see Api\OrderController).
 */
class OrderPolicy extends TenantPolicy
{
    public function viewAny(User $user): Response
    {
        return $this->allows($user, Permission::ORDERS_VIEW);
    }

    public function view(User $user, Order $order): Response
    {
        return $this->allowsFor($user, $order, Permission::ORDERS_VIEW);
    }

    public function operate(User $user, Order $order): Response
    {
        return $this->allowsFor($user, $order, Permission::KITCHEN_OPERATE);
    }
}
