<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(2),
            'default_locale' => 'en',
            'currency' => 'MWK',
            'timezone' => 'Africa/Blantyre',
            'is_active' => true,
        ];
    }
}
