<?php

namespace App\Services\Delivery;

use App\Models\DeliveryZone;
use App\Models\Kitchen;
use App\Models\Store;

/**
 * A store that can deliver to a given point, with the zone and fee that apply.
 */
final readonly class AvailableStore
{
    public function __construct(
        public Store $store,
        public ?Kitchen $kitchen,
        public DeliveryZone $zone,
        public float $distanceKm,
        public int $deliveryFee,
        public bool $isOpen,
    ) {}
}
