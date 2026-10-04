<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Driver;
use Tests\FeatureTestCase;

class DriverLiveMapTest extends FeatureTestCase
{
    public function test_live_map_shows_own_riders_deliveries_and_waiting_orders_only(): void
    {
        ['franchise' => $own, 'store' => $store] = $this->createTenant();
        ['franchise' => $other, 'store' => $otherStore] = $this->createTenant();

        // Both orders wait for a rider: nobody is online yet.
        $waiting = $this->makeReady($this->placeOrder($store));
        $delivering = $this->makeReady($this->placeOrder($store));
        $this->makeReady($this->placeOrder($otherStore));

        $busy = Driver::factory()->create([
            'franchise_id' => $own->id,
            'current_latitude' => -13.97, 'current_longitude' => 33.78, 'location_updated_at' => now()->subMinute(),
        ]);
        $delivering->update(['driver_id' => $busy->id, 'status' => OrderStatus::ON_THE_WAY]);
        $idle = Driver::factory()->onlineAt(-13.95, 33.77)->create(['franchise_id' => $own->id]);
        $idle->update(['location_updated_at' => now()->subMinutes(30)]);
        Driver::factory()->create(['franchise_id' => $own->id]); // offline, no delivery
        Driver::factory()->onlineAt(-13.95, 33.77)->create(['franchise_id' => $other->id]);

        $this->actingAsRole(UserRole::FRANCHISE_ADMIN, franchise: $own);
        $data = $this->getJson('/api/admin/drivers/live')->assertOk()->json('data');

        $this->assertSame([$busy->id, $idle->id], array_column($data['drivers'], 'id'));
        [$busyRow, $idleRow] = $data['drivers'];
        $this->assertTrue($busyRow['location_fresh']);
        $this->assertSame($delivering->id, $busyRow['delivery']['order_id']);
        $this->assertSame('ON_THE_WAY', $busyRow['delivery']['status']);
        $this->assertEqualsWithDelta($delivering->delivery_latitude, $busyRow['delivery']['dropoff']['latitude'], 0.0001);
        $this->assertFalse($idleRow['location_fresh']);
        $this->assertNull($idleRow['delivery']);

        $this->assertSame([$waiting->id], array_column($data['waiting_orders'], 'order_id'));
        $this->assertSame([$store->id], array_column($data['stores'], 'id'));
        $this->assertSame(10, $data['fresh_minutes']);
    }

    public function test_kitchen_staff_cannot_see_the_live_map(): void
    {
        ['store' => $store] = $this->createTenant();
        $this->actingAsRole(UserRole::KITCHEN_STAFF, $store);

        $this->getJson('/api/admin/drivers/live')->assertStatus(403);
    }
}
