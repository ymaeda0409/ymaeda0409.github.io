<?php

namespace App\Http\Resources\Admin;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Back-office / kitchen view. Item names are shown in the *viewer's* language
 * (current translation) with the order-time snapshot as fallback, so kitchen staff
 * read the menu in their own language whatever language the customer ordered in.
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
            'organization_id' => $this->organization_id,
            'franchise_id' => $this->franchise_id,
            'store_id' => $this->store_id,
            'kitchen_id' => $this->kitchen_id,
            'status' => $this->status->value,
            'payment_status' => $this->payment_status->value,
            'payment_method' => $this->payment_method->value,
            'is_payable' => $this->isPayable(),
            'currency' => $this->currency,
            'subtotal' => $this->subtotal,
            'delivery_fee' => $this->delivery_fee,
            'service_fee' => $this->service_fee,
            'discount' => $this->discount,
            'total' => $this->total,
            'locale' => $this->locale,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'phone' => $this->customer->phone,
            ]),
            'delivery_address' => $this->delivery_address_snapshot,
            'delivery_distance_km' => $this->delivery_distance_km,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'name' => $item->product?->translate('name') ?? $item->product_name_snapshot,
                'name_snapshot' => $item->product_name_snapshot,
                'quantity' => $item->quantity,
                'total' => $item->total,
                'options' => $item->options->map(fn ($o) => [
                    'name' => $o->option?->translate('name') ?? $o->option_name_snapshot,
                    'name_snapshot' => $o->option_name_snapshot,
                ])->all(),
            ])->all()),
            'timeline' => $this->whenLoaded('statusHistories', fn () => $this->statusHistories->map(fn ($h) => [
                'status' => $h->to_status->value,
                'at' => $h->created_at->toIso8601String(),
                'actor_user_id' => $h->actor_user_id,
                'reason_code' => $h->reason_code,
            ])->all()),
            'cancel_reason_code' => $this->cancel_reason_code,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'ordered_at' => $this->ordered_at->toIso8601String(),
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'ready_at' => $this->ready_at?->toIso8601String(),
        ];
    }
}
