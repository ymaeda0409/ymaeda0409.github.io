<?php

namespace Database\Factories;

use App\Enums\DriverStatus;
use App\Enums\UserRole;
use App\Enums\VehicleType;
use App\Models\Driver;
use App\Models\Franchise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    public function definition(): array
    {
        return [
            'franchise_id' => Franchise::factory(),
            'organization_id' => fn (array $a) => Franchise::find($a['franchise_id'])->organization_id,
            'user_id' => fn (array $a) => User::factory()->create([
                'role' => UserRole::DRIVER,
                'organization_id' => $a['organization_id'],
                'franchise_id' => $a['franchise_id'],
            ])->id,
            'vehicle_type' => VehicleType::MOTORBIKE,
            'vehicle_number' => strtoupper(fake()->bothify('LL ####')),
            'status' => DriverStatus::ACTIVE,
            'is_online' => false,
        ];
    }

    /** Online with a fresh GPS fix at the given point. */
    public function onlineAt(float $latitude, float $longitude): static
    {
        return $this->state(fn () => [
            'is_online' => true,
            'current_latitude' => $latitude,
            'current_longitude' => $longitude,
            'location_updated_at' => now(),
        ]);
    }
}
