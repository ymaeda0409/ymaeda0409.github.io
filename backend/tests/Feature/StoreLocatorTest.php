<?php

namespace Tests\Feature;

use App\Enums\FranchiseStatus;
use App\Services\Delivery\StoreLocatorService;
use Tests\FeatureTestCase;

class StoreLocatorTest extends FeatureTestCase
{
    // Lilongwe city centre, where the default factory store is located.
    private const LAT = -13.9626;

    private const LNG = 33.7741;

    public function test_point_inside_zone_returns_store_with_fee(): void
    {
        ['store' => $store, 'kitchen' => $kitchen, 'zone' => $zone] = $this->createTenant();

        $results = app(StoreLocatorService::class)->findAvailableStores(-13.9700, 33.7800);

        $this->assertCount(1, $results);
        $match = $results->first();
        $this->assertTrue($match->store->is($store));
        $this->assertTrue($match->kitchen->is($kitchen));
        $this->assertTrue($match->zone->is($zone));
        $this->assertEqualsWithDelta(1.04, $match->distanceKm, 0.05);
        $this->assertSame(150000, $match->deliveryFee);
    }

    public function test_point_outside_every_zone_returns_nothing(): void
    {
        $this->createTenant();

        // Blantyre is ~240 km from Lilongwe.
        $this->assertCount(0, app(StoreLocatorService::class)->findAvailableStores(-15.7861, 35.0058));
        // ~12 km north: beyond the 10 km max distance.
        $this->assertCount(0, app(StoreLocatorService::class)->findAvailableStores(-13.8550, 33.7741));
    }

    public function test_distance_fee_beyond_base_distance(): void
    {
        $this->createTenant();

        // ~5.5 km north → 3 started km beyond the 3 km base.
        $match = app(StoreLocatorService::class)->findAvailableStores(-13.9131, 33.7741)->first();

        $this->assertEqualsWithDelta(5.5, $match->distanceKm, 0.1);
        $this->assertSame(150000 + 3 * 30000, $match->deliveryFee);
    }

    public function test_kitchen_location_is_the_delivery_origin(): void
    {
        ['kitchen' => $kitchen] = $this->createTenant();
        // Kitchen 8 km north of the store: a point 14 km north of the store is only ~6 km from the kitchen.
        $kitchen->update(['latitude' => -13.8907, 'longitude' => 33.7741]);

        $match = app(StoreLocatorService::class)->findAvailableStores(-13.8367, 33.7741)->first();

        $this->assertNotNull($match);
        $this->assertEqualsWithDelta(6.0, $match->distanceKm, 0.1);
    }

    public function test_inactive_zone_store_or_franchise_is_excluded(): void
    {
        ['zone' => $zone] = $this->createTenant();
        $zone->update(['is_active' => false]);
        $this->assertCount(0, app(StoreLocatorService::class)->findAvailableStores(self::LAT, self::LNG));

        ['store' => $store] = $this->createTenant();
        $store->update(['is_active' => false]);
        $this->assertCount(0, app(StoreLocatorService::class)->findAvailableStores(self::LAT, self::LNG));

        ['franchise' => $franchise] = $this->createTenant();
        $franchise->update(['status' => FranchiseStatus::SUSPENDED]);
        $this->assertCount(0, app(StoreLocatorService::class)->findAvailableStores(self::LAT, self::LNG));
    }

    public function test_multiple_stores_sorted_by_distance_and_cheapest_zone_wins(): void
    {
        ['store' => $far] = $this->createTenant(['latitude' => -13.9900, 'longitude' => 33.7741]);
        ['store' => $near, 'kitchen' => $nearKitchen] = $this->createTenant(['latitude' => -13.9650, 'longitude' => 33.7741]);
        $nearKitchen->update(['latitude' => null, 'longitude' => null]);
        // A second, cheaper zone for the near store.
        $near->deliveryZones()->create([
            'organization_id' => $near->organization_id, 'franchise_id' => $near->franchise_id,
            'name' => 'Promo', 'base_fee' => 50000, 'base_distance_km' => 5, 'additional_fee_per_km' => 0,
            'max_delivery_distance_km' => 5, 'is_active' => true,
        ]);

        $results = app(StoreLocatorService::class)->findAvailableStores(self::LAT, self::LNG);

        $this->assertSame([$near->id, $far->id], $results->map(fn ($m) => $m->store->id)->all());
        $this->assertSame(50000, $results->first()->deliveryFee);
    }

    public function test_available_stores_endpoint_returns_localized_store_info(): void
    {
        ['store' => $store] = $this->createTenant();
        $store->translations()->create(['locale' => 'en', 'description' => 'Fresh bento']);
        $store->translations()->create(['locale' => 'ja', 'description' => 'できたて弁当']);

        $this->getJson('/api/stores/available?latitude=-13.9700&longitude=33.7800', ['Accept-Language' => 'ja'])
            ->assertOk()
            ->assertJsonPath('data.0.store.id', $store->id)
            ->assertJsonPath('data.0.store.description', 'できたて弁当')
            ->assertJsonPath('data.0.delivery_fee', 150000)
            ->assertJsonPath('data.0.currency', 'MWK')
            ->assertJsonPath('data.0.is_open', true);

        // ny has no store translation → English fallback.
        $this->getJson('/api/stores/available?latitude=-13.9700&longitude=33.7800', ['Accept-Language' => 'ny'])
            ->assertJsonPath('data.0.store.description', 'Fresh bento');
    }

    public function test_available_stores_validates_coordinates(): void
    {
        $this->getJson('/api/stores/available?latitude=200&longitude=33')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }
}
