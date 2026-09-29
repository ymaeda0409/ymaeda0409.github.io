<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Customer view of an order. Item names are the snapshots taken in the order's language.
 * The delivery PIN is only ever shown to the customer who owns the order.
 *
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'payment_status' => $this->payment_status->value,
            'payment_method' => $this->payment_method->value,
            'currency' => $this->currency,
            'subtotal' => $this->subtotal,
            'delivery_fee' => $this->delivery_fee,
            'service_fee' => $this->service_fee,
            'discount' => $this->discount,
            'total' => $this->total,
            'delivery_pin' => $this->when($request->user()?->id === $this->customer_id, $this->delivery_pin),
            'delivery_address' => $this->delivery_address_snapshot,
            'delivery_latitude' => $this->delivery_latitude,
            'delivery_longitude' => $this->delivery_longitude,
            'store' => $this->whenLoaded('store', fn () => [
                'id' => $this->store->id,
                'name' => $this->store->name,
                'phone' => $this->store->phone,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'name' => $item->product_name_snapshot,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'option_amount' => $item->option_amount,
                'total' => $item->total,
                'options' => $item->options->map(fn ($o) => ['name' => $o->option_name_snapshot, 'price' => $o->price])->all(),
            ])->all()),
            'item_count' => $this->whenLoaded('items', fn () => $this->items->sum('quantity')),
            'timeline' => $this->whenLoaded('statusHistories', fn () => $this->statusHistories->map(fn ($h) => [
                'status' => $h->to_status->value,
                'at' => $h->created_at->toIso8601String(),
            ])->all()),
            'cancel_reason_code' => $this->cancel_reason_code,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'ordered_at' => $this->ordered_at->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
        ];
    }
}
