<?php

namespace App\Http\Resources;

use App\Models\Driver;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Live tracking for the customer: status, pickup/drop-off points and the rider's
 * last known position while the delivery is in progress.
 *
 * @mixin Order
 */
class OrderTrackingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $showDriver = $this->driver && in_array($this->status, Driver::ACTIVE_DELIVERY_STATUSES, true);

        return [
            'order_id' => $this->id,
            'status' => $this->status->value,
            'pickup' => [
                'latitude' => $this->kitchen?->latitude ?? $this->store->latitude,
                'longitude' => $this->kitchen?->longitude ?? $this->store->longitude,
            ],
            'dropoff' => ['latitude' => $this->delivery_latitude, 'longitude' => $this->delivery_longitude],
            'driver' => $showDriver ? [
                'name' => $this->driver->user?->name,
                'vehicle_type' => $this->driver->vehicle_type->value,
                'latitude' => $this->driver->current_latitude,
                'longitude' => $this->driver->current_longitude,
                'updated_at' => $this->driver->location_updated_at?->toIso8601String(),
            ] : null,
            'timeline' => $this->statusHistories->map(fn ($h) => [
                'status' => $h->to_status->value,
                'at' => $h->created_at->toIso8601String(),
            ])->all(),
        ];
    }
}
