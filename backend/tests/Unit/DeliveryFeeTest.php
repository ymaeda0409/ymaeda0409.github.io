<?php

namespace Tests\Unit;

use App\Models\DeliveryZone;
use App\Support\Geo;
use Tests\TestCase;

class DeliveryFeeTest extends TestCase
{
    private function zone(): DeliveryZone
    {
        return new DeliveryZone([
            'base_fee' => 150000,
            'base_distance_km' => 3,
            'additional_fee_per_km' => 30000,
            'max_delivery_distance_km' => 10,
        ]);
    }

    public function test_base_fee_applies_within_base_distance(): void
    {
        $this->assertSame(150000, $this->zone()->feeForDistance(0.5));
        $this->assertSame(150000, $this->zone()->feeForDistance(3.0));
    }

    public function test_each_started_km_beyond_base_is_charged(): void
    {
        $this->assertSame(180000, $this->zone()->feeForDistance(3.2));
        $this->assertSame(180000, $this->zone()->feeForDistance(4.0));
        $this->assertSame(270000, $this->zone()->feeForDistance(6.5));
    }

    public function test_coverage_is_bounded_by_max_distance(): void
    {
        $this->assertTrue($this->zone()->coversDistance(10.0));
        $this->assertFalse($this->zone()->coversDistance(10.01));
    }

    public function test_haversine_distance(): void
    {
        // Lilongwe → Blantyre is roughly 230 km as the crow flies.
        $distance = Geo::distanceKm(-13.9626, 33.7741, -15.7861, 35.0058);
        $this->assertEqualsWithDelta(240, $distance, 15);
        $this->assertEqualsWithDelta(0.0, Geo::distanceKm(-13.9626, 33.7741, -13.9626, 33.7741), 0.0001);
    }
}
