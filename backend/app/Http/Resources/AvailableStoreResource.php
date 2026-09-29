<?php

namespace App\Http\Resources;

use App\Services\Delivery\AvailableStore;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AvailableStore */
class AvailableStoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'store' => (new StoreResource($this->store))->toArray($request),
            'kitchen_id' => $this->kitchen?->id,
            'delivery_zone_id' => $this->zone->id,
            'distance_km' => $this->distanceKm,
            'delivery_fee' => $this->deliveryFee,
            'currency' => $this->store->currency,
            'is_open' => $this->isOpen,
        ];
    }
}
