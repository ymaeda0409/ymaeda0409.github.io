<?php

namespace App\Http\Resources;

use App\Models\DeliveryAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DeliveryAssignment */
class DeliveryRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'expires_at' => $this->expires_at->toIso8601String(),
            'expires_in' => max(0, (int) now()->diffInSeconds($this->expires_at, false)),
            'distance_to_pickup_km' => $this->distance_km,
            'delivery' => new DriverDeliveryResource($this->order),
        ];
    }
}
