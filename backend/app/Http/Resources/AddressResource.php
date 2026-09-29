<?php

namespace App\Http\Resources;

use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UserAddress */
class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'area' => $this->area,
            'street' => $this->street,
            'building' => $this->building,
            'landmark' => $this->landmark,
            'delivery_note' => $this->delivery_note,
            'phone' => $this->phone,
            'is_default' => $this->is_default,
        ];
    }
}
