<?php

namespace App\Services\Delivery;

use App\Enums\FranchiseStatus;
use App\Models\DeliveryZone;
use App\Models\Store;
use App\Support\Geo;
use Illuminate\Support\Collection;

class StoreLocatorService
{
    /**
     * Stores whose active delivery zones cover the given point, nearest first.
     * When several zones of one store match, the cheapest one is used.
     *
     * @return Collection<int, AvailableStore>
     */
    public function findAvailableStores(float $latitude, float $longitude): Collection
    {
        $box = Geo::boundingBox($latitude, $longitude, (float) config('bento.geo.max_zone_radius_km'));

        $zones = DeliveryZone::query()
            ->where('is_active', true)
            ->whereHas('store', function ($query) use ($box) {
                $query->active()
                    ->whereBetween('latitude', [$box['minLat'], $box['maxLat']])
                    ->whereBetween('longitude', [$box['minLng'], $box['maxLng']])
                    ->whereHas('franchise', fn ($q) => $q->where('status', FranchiseStatus::ACTIVE));
            })
            ->with([
                'store' => fn ($q) => $q->withTranslations(),
                'store.kitchens' => fn ($q) => $q->where('is_active', true)->orderBy('id'),
                'kitchen',
            ])
            ->get();

        return $zones
            ->map(fn (DeliveryZone $zone) => $this->match($zone, $latitude, $longitude))
            ->filter()
            ->groupBy(fn (AvailableStore $match) => $match->store->id)
            ->map(fn (Collection $matches) => $matches->sortBy('deliveryFee')->first())
            ->sortBy('distanceKm')
            ->values();
    }

    /**
     * Matches a point against one store's zones (used when placing orders).
     */
    public function matchStore(Store $store, float $latitude, float $longitude): ?AvailableStore
    {
        return $this->findAvailableStores($latitude, $longitude)
            ->first(fn (AvailableStore $match) => $match->store->is($store));
    }

    private function match(DeliveryZone $zone, float $latitude, float $longitude): ?AvailableStore
    {
        $kitchen = $zone->kitchen && $zone->kitchen->is_active ? $zone->kitchen : null;
        $originLat = $kitchen?->latitude ?? $zone->store->latitude;
        $originLng = $kitchen?->longitude ?? $zone->store->longitude;

        $distance = Geo::distanceKm($originLat, $originLng, $latitude, $longitude);

        if (! $zone->coversDistance($distance)) {
            return null;
        }

        return new AvailableStore(
            store: $zone->store,
            kitchen: $kitchen ?? $zone->store->kitchens->first(),
            zone: $zone,
            distanceKm: round($distance, 2),
            deliveryFee: $zone->feeForDistance($distance),
            isOpen: $zone->store->isOpenNow(),
        );
    }
}
