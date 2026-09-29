<?php

namespace App\Http\Resources\Admin;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Store */
class StoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'franchise_id' => $this->franchise_id,
            'code' => $this->code,
            'name' => $this->name,
            'business_type' => $this->business_type->value,
            'phone' => $this->phone,
            'email' => $this->email,
            'city' => $this->city,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'timezone' => $this->timezone,
            'currency' => $this->currency,
            'opening_hours' => $this->opening_hours,
            'is_active' => $this->is_active,
            'is_accepting_orders' => $this->is_accepting_orders,
            'is_open' => $this->isOpenNow(),
            'translations' => $this->translationsByLocale(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
