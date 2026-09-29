<?php

namespace Tests\Feature;

use App\Enums\AssignmentStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\DeliveryAssignment;
use App\Models\Driver;
use App\Models\DriverLocation;
use App\Models\Franchise;
use App\Models\Order;
use App\Models\Store;
use App\Services\Delivery\DeliveryAssignmentService;
use Laravel\Sanctum\Sanctum;
use Tests\FeatureTestCase;

class DeliveryTest extends FeatureTestCase
{
    private Store $store;

    private Franchise $franchise;

    protected function setUp(): void
    {
        parent::setUp();
        ['store' => $this->store, 'franchise' => $this->franchise] = $this->createTenant();
    }

    /** Driver of this franchise, online, `km` north of the store. */
    private function driverAt(float $km, array $attributes = []): Driver
    {
        return Driver::factory()
            ->onlineAt($this->store->latitude + $km / 111.2, $this->store->longitude)
            ->create(['franchise_id' => $this->franchise->id] + $attributes);
    }

    /** A fresh user instance per login, like a real request (no cached relations). */
    private function actingAsDriver(Driver $driver): void
    {
        Sanctum::actingAs($driver->user()->first());
    }

    private function offerOf(Order $order): ?DeliveryAssignment
    {
        return $order->assignments()->where('status', AssignmentStatus::OFFERED)->first();
    }

    public function test_ready_order_is_offered_to_the_nearest_free_online_driver(): void
    {
        $far = $this->driverAt(3.0);
        $near = $this->driverAt(0.5);
        $this->driverAt(0.1, ['is_online' => false]);
        $this->driverAt(0.2, ['status' => 'SUSPENDED']);
        $this->driverAt(0.3, ['location_updated_at' => now()->subHour()]);
        Driver::factory()->onlineAt($this->store->latitude, $this->store->longitude)->create(); // other franchise
        $busy = $this->driverAt(0.05);
        $this->makeReady($this->placeOrder($this->store))->update(['driver_id' => $busy->id, 'status' => OrderStatus::ON_THE_WAY]);

        $order = $this->makeReady($this->placeOrder($this->store));

        $offer = $this->offerOf($order);
        $this->assertNotNull($offer);
        $this->assertSame($near->id, $offer->driver_id);
        $this->assertEqualsWithDelta(0.5, $offer->distance_km, 0.05);
        $this->assertNotSame($far->id, $offer->driver_id);
    }

    public function test_store_bound_driver_only_serves_that_store(): void
    {
        $otherStore = Store::factory()->create(['franchise_id' => $this->franchise->id]);
        $this->driverAt(0.1, ['store_id' => $otherStore->id]);
        $floating = $this->driverAt(2.0);

        $order = $this->makeReady($this->placeOrder($this->store));

        $this->assertSame($floating->id, $this->offerOf($order)->driver_id);
    }

    public function test_driver_sees_offer_and_accepts_it(): void
    {
        $driver = $this->driverAt(0.5);
        $order = $this->makeReady($this->placeOrder($this->store));
        $this->actingAsDriver($driver);

        $this->getJson('/api/driver/delivery-requests')
            ->assertOk()
            ->assertJsonPath('data.0.order_id', $order->id)
            ->assertJsonPath('data.0.delivery.amount_to_collect', $order->total)
            ->assertJsonMissingPath('data.0.delivery.delivery_pin');

        $this->postJson("/api/driver/deliveries/{$order->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', 'RIDER_ASSIGNED');

        $this->assertSame($driver->id, $order->fresh()->driver_id);
        $this->getJson('/api/driver/me')->assertJsonPath('data.active_delivery.id', $order->id);
    }

    public function test_declined_offer_moves_to_the_next_driver(): void
    {
        $first = $this->driverAt(0.2);
        $second = $this->driverAt(1.0);
        $order = $this->makeReady($this->placeOrder($this->store));
        $this->actingAsDriver($first);

        $this->postJson("/api/driver/deliveries/{$order->id}/decline")->assertOk();

        $this->assertSame($second->id, $this->offerOf($order)->driver_id);
        $this->postJson("/api/driver/deliveries/{$order->id}/accept")
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'OFFER_NOT_AVAILABLE');
    }

    public function test_expired_offer_moves_on_via_the_dispatcher(): void
    {
        $first = $this->driverAt(0.2);
        $second = $this->driverAt(1.0);
        $order = $this->makeReady($this->placeOrder($this->store));

        $this->travel(61)->seconds();
        // Keep GPS fresh after time travel.
        Driver::query()->update(['location_updated_at' => now()]);
        $this->artisan('deliveries:dispatch')->assertSuccessful();

        $this->assertSame($second->id, $this->offerOf($order)->driver_id);
        $this->assertSame(AssignmentStatus::EXPIRED, $order->assignments()->where('driver_id', $first->id)->first()->status);

        $this->actingAsDriver($first);
        $this->postJson("/api/driver/deliveries/{$order->id}/accept")->assertStatus(409);
    }

    public function test_order_waits_until_a_driver_comes_online(): void
    {
        $order = $this->makeReady($this->placeOrder($this->store));
        $this->assertNull($this->offerOf($order));

        $driver = Driver::factory()->create(['franchise_id' => $this->franchise->id]);
        $this->actingAsDriver($driver);
        $this->postJson('/api/driver/online', ['latitude' => $this->store->latitude, 'longitude' => $this->store->longitude])
            ->assertOk()
            ->assertJsonPath('data.is_online', true);

        $this->assertSame($driver->id, $this->offerOf($order)->driver_id);
    }

    public function test_going_offline_passes_the_offer_on(): void
    {
        $first = $this->driverAt(0.2);
        $second = $this->driverAt(1.0);
        $order = $this->makeReady($this->placeOrder($this->store));
        $this->actingAsDriver($first);

        $this->postJson('/api/driver/offline')->assertOk();

        $this->assertSame($second->id, $this->offerOf($order)->driver_id);
    }

    public function test_busy_driver_gets_no_second_offer(): void
    {
        $driver = $this->driverAt(0.2);
        $first = $this->makeReady($this->placeOrder($this->store));
        $second = $this->makeReady($this->placeOrder($this->store));

        $this->assertSame($driver->id, $this->offerOf($first)->driver_id);
        $this->assertNull($this->offerOf($second), 'one open offer per driver');

        $this->actingAsDriver($driver);
        $this->postJson("/api/driver/deliveries/{$first->id}/accept")->assertOk();
        $this->artisan('deliveries:dispatch');
        $this->assertNull($this->offerOf($second), 'drivers with an active delivery are skipped');
    }

    public function test_full_delivery_with_pin(): void
    {
        $driver = $this->driverAt(0.2);
        $order = $this->makeReady($this->placeOrder($this->store));
        $this->actingAsDriver($driver);
        $this->postJson("/api/driver/deliveries/{$order->id}/accept")->assertOk();

        $this->postJson("/api/driver/deliveries/{$order->id}/complete", ['pin' => $order->delivery_pin])
            ->assertJsonPath('error.code', 'INVALID_STATUS_TRANSITION');

        $this->postJson("/api/driver/deliveries/{$order->id}/pickup")->assertOk()->assertJsonPath('data.status', 'ON_THE_WAY');
        $this->postJson("/api/driver/deliveries/{$order->id}/arrive")->assertOk()->assertJsonPath('data.status', 'ARRIVED');

        $wrong = $order->delivery_pin === '0000' ? '1111' : '0000';
        $this->postJson("/api/driver/deliveries/{$order->id}/complete", ['pin' => $wrong])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'DELIVERY_PIN_INVALID');

        $this->postJson("/api/driver/deliveries/{$order->id}/complete", ['pin' => $order->delivery_pin])
            ->assertOk()
            ->assertJsonPath('data.status', 'DELIVERED')
            ->assertJsonPath('data.amount_to_collect', 0);

        $order->refresh();
        $this->assertSame('PAID', $order->payment_status->value, 'cash is collected at hand-over');
        $this->assertNotNull($order->delivered_at);
        $this->assertSame(
            ['NEW', 'CONFIRMED', 'COOKING', 'READY_FOR_PICKUP', 'RIDER_ASSIGNED', 'PICKED_UP', 'ON_THE_WAY', 'ARRIVED', 'DELIVERED'],
            $order->statusHistories->map(fn ($h) => $h->to_status->value)->all(),
        );
        $this->getJson('/api/driver/me')->assertJsonPath('data.active_delivery', null);
    }

    public function test_pin_locks_after_too_many_wrong_attempts(): void
    {
        $driver = $this->driverAt(0.2);
        $order = $this->makeReady($this->placeOrder($this->store));
        $order->update(['driver_id' => $driver->id, 'status' => OrderStatus::ARRIVED]);
        $this->actingAsDriver($driver);
        $wrong = $order->delivery_pin === '0000' ? '1111' : '0000';

        for ($i = 0; $i < 5; $i++) {
            $this->postJson("/api/driver/deliveries/{$order->id}/complete", ['pin' => $wrong])->assertJsonPath('error.code', 'DELIVERY_PIN_INVALID');
        }

        $this->postJson("/api/driver/deliveries/{$order->id}/complete", ['pin' => $order->delivery_pin])
            ->assertJsonPath('error.code', 'DELIVERY_PIN_LOCKED');
        $this->assertSame(OrderStatus::ARRIVED, $order->fresh()->status);
    }

    public function test_drivers_cannot_touch_other_drivers_deliveries(): void
    {
        $owner = $this->driverAt(0.2);
        $other = $this->driverAt(0.5);
        $order = $this->makeReady($this->placeOrder($this->store));
        $this->actingAsDriver($owner);
        $this->postJson("/api/driver/deliveries/{$order->id}/accept")->assertOk();

        $this->actingAsDriver($other);
        $this->getJson("/api/driver/deliveries/{$order->id}")->assertStatus(404);
        $this->postJson("/api/driver/deliveries/{$order->id}/pickup")->assertStatus(404);
        $this->postJson("/api/driver/deliveries/{$order->id}/complete", ['pin' => $order->delivery_pin])->assertStatus(404);
    }

    public function test_non_driver_roles_are_rejected(): void
    {
        $this->actingAsRole(UserRole::CUSTOMER);
        $this->getJson('/api/driver/me')->assertStatus(403);

        // A DRIVER user without a driver profile.
        $this->actingAsRole(UserRole::DRIVER);
        $this->getJson('/api/driver/me')->assertStatus(403)->assertJsonPath('error.code', 'FORBIDDEN');

        $suspended = $this->driverAt(0.1, ['status' => 'SUSPENDED']);
        $this->actingAsDriver($suspended);
        $this->postJson('/api/driver/online')->assertStatus(403)->assertJsonPath('error.code', 'ACCOUNT_DISABLED');
    }

    public function test_gps_points_are_stored_and_the_newest_wins(): void
    {
        $driver = $this->driverAt(0.2);
        $order = $this->makeReady($this->placeOrder($this->store));
        $this->actingAsDriver($driver);
        $this->postJson("/api/driver/deliveries/{$order->id}/accept")->assertOk();
        $foreign = $this->placeOrder($this->store);
        $driver->update(['location_updated_at' => now()->subMinutes(5)]);
        $this->actingAsDriver($driver);

        // Offline buffer: sent late and out of order.
        $this->postJson('/api/driver/location', ['points' => [
            ['latitude' => -13.9700, 'longitude' => 33.7800, 'timestamp' => now()->subSeconds(10)->toIso8601String(), 'order_id' => $order->id],
            ['latitude' => -13.9650, 'longitude' => 33.7750, 'timestamp' => now()->subSeconds(30)->toIso8601String(), 'order_id' => $order->id],
            ['latitude' => -13.9600, 'longitude' => 33.7700, 'timestamp' => now()->subSeconds(20)->toIso8601String(), 'order_id' => $foreign->id],
        ]])->assertOk()->assertJsonPath('data.stored', 3);

        $driver->refresh();
        $this->assertEqualsWithDelta(-13.9700, $driver->current_latitude, 0.00001);
        $this->assertSame(2, DriverLocation::where('order_id', $order->id)->count());

        // An older single point does not move the rider back.
        $this->postJson('/api/driver/location', ['latitude' => -13.9500, 'longitude' => 33.7600, 'timestamp' => now()->subMinute()->toIso8601String()])->assertOk();
        $this->assertEqualsWithDelta(-13.9700, $driver->fresh()->current_latitude, 0.00001);
    }

    public function test_customer_tracks_the_rider_only_during_delivery(): void
    {
        $driver = $this->driverAt(0.2);
        $order = $this->makeReady($this->placeOrder($this->store));
        $customer = $order->customer;

        Sanctum::actingAs($customer);
        $this->getJson("/api/orders/{$order->id}/tracking")
            ->assertOk()
            ->assertJsonPath('data.status', 'READY_FOR_PICKUP')
            ->assertJsonPath('data.driver', null);

        app(DeliveryAssignmentService::class)->accept($driver, $order);
        $tracking = $this->getJson("/api/orders/{$order->id}/tracking")->assertJsonPath('data.driver.vehicle_type', 'MOTORBIKE');
        $this->assertEqualsWithDelta($driver->current_latitude, $tracking->json('data.driver.latitude'), 0.000001);

        $this->actingAsRole(UserRole::CUSTOMER);
        $this->getJson("/api/orders/{$order->id}/tracking")->assertStatus(404);
    }

    public function test_cancelling_a_ready_order_withdraws_the_offer(): void
    {
        $driver = $this->driverAt(0.2);
        $order = $this->makeReady($this->placeOrder($this->store));
        $this->assertNotNull($this->offerOf($order));

        $this->actingAsRole(UserRole::STORE_MANAGER, $this->store);
        $this->postJson("/api/admin/orders/{$order->id}/cancel")->assertOk();

        $this->assertNull($this->offerOf($order));
        $this->actingAsDriver($driver);
        $this->getJson('/api/driver/delivery-requests')->assertJsonCount(0, 'data');
    }
}
