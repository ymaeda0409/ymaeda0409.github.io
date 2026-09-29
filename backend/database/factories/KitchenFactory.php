<?php

namespace Database\Factories;

use App\Models\Kitchen;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kitchen>
 */
class KitchenFactory extends Factory
{
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'organization_id' => fn (array $a) => Store::find($a['store_id'])->organization_id,
            'franchise_id' => fn (array $a) => Store::find($a['store_id'])->franchise_id,
            'name' => fake()->city().' Kitchen',
            'latitude' => null,
            'longitude' => null,
            'is_active' => true,
        ];
    }
}
