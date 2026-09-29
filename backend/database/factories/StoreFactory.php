<?php

namespace Database\Factories;

use App\Models\Franchise;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'franchise_id' => Franchise::factory(),
            'organization_id' => fn (array $attributes) => Franchise::find($attributes['franchise_id'])->organization_id,
            'code' => strtoupper(fake()->unique()->bothify('ST-####')),
            'name' => fake()->city().' Store',
            'business_type' => 'FOOD',
            'city' => 'Lilongwe',
            // Lilongwe city centre by default.
            'latitude' => -13.9626,
            'longitude' => 33.7741,
            'timezone' => 'Africa/Blantyre',
            'currency' => 'MWK',
            'opening_hours' => null,
            'is_active' => true,
            'is_accepting_orders' => true,
        ];
    }

    public function at(float $latitude, float $longitude): static
    {
        return $this->state(fn () => ['latitude' => $latitude, 'longitude' => $longitude]);
    }
}
