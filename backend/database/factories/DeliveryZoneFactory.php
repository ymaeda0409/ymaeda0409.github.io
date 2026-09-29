<?php

namespace Database\Factories;

use App\Models\DeliveryZone;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryZone>
 */
class DeliveryZoneFactory extends Factory
{
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'organization_id' => fn (array $a) => Store::find($a['store_id'])->organization_id,
            'franchise_id' => fn (array $a) => Store::find($a['store_id'])->franchise_id,
            'kitchen_id' => null,
            'name' => 'Standard',
            'base_fee' => 150000,
            'base_distance_km' => 3,
            'additional_fee_per_km' => 30000,
            'max_delivery_distance_km' => 10,
            'is_active' => true,
        ];
    }
}
