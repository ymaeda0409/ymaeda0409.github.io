<?php

namespace Database\Factories;

use App\Enums\FranchiseStatus;
use App\Models\Franchise;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Franchise>
 */
class FranchiseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'code' => strtoupper(fake()->unique()->bothify('FC-####')),
            'name' => fake()->city().' Franchise',
            'owner_name' => fake()->name(),
            'region' => fake()->randomElement(['Central', 'Southern', 'Northern']),
            'status' => FranchiseStatus::ACTIVE,
            'commission_rate' => 10,
        ];
    }
}
