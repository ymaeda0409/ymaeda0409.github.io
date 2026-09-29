<?php

namespace App\Http\Resources\Admin;

use App\Models\Kitchen;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Kitchen */
class KitchenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'franchise_id' => $this->franchise_id,
            'store_id' => $this->store_id,
            'name' => $this->name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_active' => $this->is_active,
        ];
    }
}
