<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\UserAddress;
use Tests\FeatureTestCase;

class AddressTest extends FeatureTestCase
{
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Home',
            'latitude' => -13.97,
            'longitude' => 33.78,
            'area' => 'Area 47',
            'landmark' => 'Near the blue gate',
            'phone' => '0991234567',
        ] + $overrides;
    }

    public function test_customer_manages_addresses_with_single_default(): void
    {
        $user = $this->actingAsRole(UserRole::CUSTOMER);

        $first = $this->postJson('/api/addresses', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.is_default', true)
            ->assertJsonPath('data.phone', '+265991234567')
            ->json('data.id');

        $second = $this->postJson('/api/addresses', $this->payload(['name' => 'Office', 'is_default' => true]))
            ->assertCreated()
            ->json('data.id');

        $this->assertFalse(UserAddress::find($first)->is_default);
        $this->assertTrue(UserAddress::find($second)->is_default);

        $this->putJson("/api/addresses/{$first}", ['landmark' => 'Opposite the mosque'])
            ->assertOk()
            ->assertJsonPath('data.landmark', 'Opposite the mosque');

        $this->getJson('/api/addresses')->assertJsonCount(2, 'data')->assertJsonPath('data.0.id', $second);

        $this->deleteJson("/api/addresses/{$second}")->assertOk();
        $this->assertTrue(UserAddress::find($first)->is_default);
        $this->assertSame(1, $user->addresses()->count());
    }

    public function test_cannot_touch_another_customers_address(): void
    {
        $other = UserAddress::factory()->create();
        $this->actingAsRole(UserRole::CUSTOMER);

        $this->putJson("/api/addresses/{$other->id}", ['name' => 'Hacked'])
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND');
        $this->deleteJson("/api/addresses/{$other->id}")->assertStatus(404);
        $this->assertSame('Home', $other->fresh()->name);
    }

    public function test_address_requires_gps_coordinates(): void
    {
        $this->actingAsRole(UserRole::CUSTOMER);

        $this->postJson('/api/addresses', ['name' => 'Home'])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['fields' => ['latitude', 'longitude']]]);
    }

    public function test_staff_cannot_use_customer_address_book(): void
    {
        ['store' => $store] = $this->createTenant();
        $this->actingAsRole(UserRole::STORE_MANAGER, $store);

        $this->getJson('/api/addresses')->assertStatus(403)->assertJsonPath('error.code', 'FORBIDDEN');
    }
}
