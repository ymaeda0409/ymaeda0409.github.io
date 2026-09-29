<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Auth\Access\Response;

class UserAddressPolicy
{
    public function manage(User $user, UserAddress $address): Response
    {
        return $address->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }
}
