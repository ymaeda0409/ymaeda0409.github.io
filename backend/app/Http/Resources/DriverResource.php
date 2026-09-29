<?php

namespace App\Http\Resources;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Driver */
class DriverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->user?->name,
            'phone' => $this->user?->phone,
            'preferred_language' => $this->user?->preferred_language,
            'franchise_id' => $this->franchise_id,
            'store_id' => $this->store_id,
            'vehicle_type' => $this->vehicle_type->value,
            'vehicle_number' => $this->vehicle_number,
            'status' => $this->status->value,
            'is_online' => $this->is_online,
            'current_latitude' => $this->current_latitude,
            'current_longitude' => $this->current_longitude,
            'location_updated_at' => $this->location_updated_at?->toIso8601String(),
            'rating' => $this->rating,
        ];
    }
}
