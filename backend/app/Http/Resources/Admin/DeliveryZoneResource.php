<?php

namespace App\Http\Resources\Admin;

use App\Models\DeliveryZone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DeliveryZone */
class DeliveryZoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'franchise_id' => $this->franchise_id,
            'store_id' => $this->store_id,
            'kitchen_id' => $this->kitchen_id,
            'name' => $this->name,
            'base_fee' => $this->base_fee,
            'base_distance_km' => $this->base_distance_km,
            'additional_fee_per_km' => $this->additional_fee_per_km,
            'max_delivery_distance_km' => $this->max_delivery_distance_km,
            'is_active' => $this->is_active,
        ];
    }
}
