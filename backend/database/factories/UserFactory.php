<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Franchise;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Default: a customer registered by phone.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '+26599'.fake()->unique()->numerify('#######'),
            'email' => null,
            'password' => null,
            'role' => UserRole::CUSTOMER,
            'preferred_language' => 'en',
            'is_active' => true,
            'phone_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    public function staff(UserRole $role): static
    {
        return $this->state(fn () => [
            'role' => $role,
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
        ]);
    }

    public function superAdmin(?int $organizationId = null): static
    {
        return $this->staff(UserRole::SUPER_ADMIN)->state(fn () => ['organization_id' => $organizationId]);
    }

    public function franchiseAdmin(Franchise $franchise): static
    {
        return $this->staff(UserRole::FRANCHISE_ADMIN)->state(fn () => [
            'organization_id' => $franchise->organization_id,
            'franchise_id' => $franchise->id,
        ]);
    }

    public function storeManager(Store $store): static
    {
        return $this->staff(UserRole::STORE_MANAGER)->forStore($store);
    }

    public function kitchenStaff(Store $store): static
    {
        return $this->staff(UserRole::KITCHEN_STAFF)->forStore($store);
    }

    public function driver(): static
    {
        return $this->state(fn () => ['role' => UserRole::DRIVER]);
    }

    private function forStore(Store $store): static
    {
        return $this->state(fn () => [
            'organization_id' => $store->organization_id,
            'franchise_id' => $store->franchise_id,
            'store_id' => $store->id,
        ]);
    }
}
