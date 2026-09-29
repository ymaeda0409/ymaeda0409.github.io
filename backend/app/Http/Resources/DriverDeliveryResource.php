<?php

namespace App\Http\Resources;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What a driver needs for a delivery. Never contains the delivery PIN — the customer
 * tells it to the rider at hand-over.
 *
 * @mixin Order
 */
class DriverDeliveryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $collectCash = $this->payment_method === PaymentMethod::CASH && $this->payment_status !== PaymentStatus::PAID;

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'payment_method' => $this->payment_method->value,
            'currency' => $this->currency,
            'amount_to_collect' => $collectCash ? $this->total : 0,
            'item_count' => $this->items->sum('quantity'),
            'pickup' => [
                'name' => $this->store->name,
                'phone' => $this->store->phone,
                'address' => $this->store->address,
                'latitude' => $this->kitchen?->latitude ?? $this->store->latitude,
                'longitude' => $this->kitchen?->longitude ?? $this->store->longitude,
            ],
            'dropoff' => [
                'name' => $this->customer?->name,
                'phone' => $this->delivery_address_snapshot['phone'] ?? $this->customer?->phone,
                'address' => $this->delivery_address_snapshot,
                'latitude' => $this->delivery_latitude,
                'longitude' => $this->delivery_longitude,
            ],
            'distance_km' => $this->delivery_distance_km,
            'pin_attempts_left' => max(0, (int) config('bento.dispatch.max_pin_attempts') - $this->delivery_pin_attempts),
            'ready_at' => $this->ready_at?->toIso8601String(),
            'picked_up_at' => $this->picked_up_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
        ];
    }
}
