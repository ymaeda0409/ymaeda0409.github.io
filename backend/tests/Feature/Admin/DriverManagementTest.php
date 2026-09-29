<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Driver;
use Tests\FeatureTestCase;

class DriverManagementTest extends FeatureTestCase
{
    public function test_franchise_admin_registers_drivers_into_own_franchise(): void
    {
        ['franchise' => $franchise] = $this->createTenant();
        $this->actingAsRole(UserRole::FRANCHISE_ADMIN, franchise: $franchise);

        $this->postJson('/api/admin/drivers', [
            'phone' => '0888123456', 'name' => 'Chikondi', 'vehicle_type' => 'BICYCLE', 'preferred_language' => 'ny',
        ])->assertCreated()
            ->assertJsonPath('data.franchise_id', $franchise->id)
            ->assertJsonPath('data.phone', '+265888123456')
            ->assertJsonPath('data.preferred_language', 'ny');

        // The new driver can log in with OTP and use the driver API.
        $this->postJson('/api/auth/send-otp', ['phone' => '0888123456']);
        $token = $this->postJson('/api/auth/verify-otp', ['phone' => '0888123456', 'code' => '123456'])
            ->assertJsonPath('data.user.role', 'DRIVER')
            ->json('data.token');
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/driver/me')->assertOk()->assertJsonPath('data.driver.vehicle_type', 'BICYCLE');
    }

    public function test_drivers_of_other_franchises_are_invisible(): void
    {
        ['franchise' => $own] = $this->createTenant();
        ['franchise' => $other] = $this->createTenant();
        $mine = Driver::factory()->create(['franchise_id' => $own->id]);
        $theirs = Driver::factory()->create(['franchise_id' => $other->id]);
        $this->actingAsRole(UserRole::FRANCHISE_ADMIN, franchise: $own);

        $this->getJson('/api/admin/drivers')->assertJsonPath('data.*.id', [$mine->id]);
        $this->getJson("/api/admin/drivers/{$theirs->id}")->assertStatus(404);
        $this->putJson("/api/admin/drivers/{$theirs->id}", ['status' => 'SUSPENDED'])->assertStatus(404);
    }

    public function test_suspending_a_driver_takes_them_offline(): void
    {
        ['franchise' => $franchise] = $this->createTenant();
        $driver = Driver::factory()->onlineAt(-13.96, 33.77)->create(['franchise_id' => $franchise->id]);
        $this->actingAsRole(UserRole::SUPER_ADMIN);

        $this->putJson("/api/admin/drivers/{$driver->id}", ['status' => 'SUSPENDED'])
            ->assertOk()
            ->assertJsonPath('data.is_online', false);
    }

    public function test_kitchen_staff_cannot_manage_drivers(): void
    {
        ['store' => $store] = $this->createTenant();
        $this->actingAsRole(UserRole::KITCHEN_STAFF, $store);

        $this->getJson('/api/admin/drivers')->assertStatus(403);
    }
}
