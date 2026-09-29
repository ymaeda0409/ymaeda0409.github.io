<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserAddress>
 */
class UserAddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'Home',
            'latitude' => -13.9700,
            'longitude' => 33.7800,
            'area' => 'Area 10',
            'landmark' => 'Near the market',
            'is_default' => false,
        ];
    }
}
