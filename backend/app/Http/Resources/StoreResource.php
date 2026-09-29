<?php

namespace App\Http\Resources;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Customer-facing store (translated fields resolved to the request locale).
 *
 * @mixin Store
 */
class StoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'business_type' => $this->business_type->value,
            'phone' => $this->phone,
            'city' => $this->city,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'currency' => $this->currency,
            'timezone' => $this->timezone,
            'is_open' => $this->isOpenNow(),
            'description' => $this->translate('description'),
            'announcement' => $this->translate('announcement'),
        ];
    }
}
