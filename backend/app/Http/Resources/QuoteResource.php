<?php

namespace App\Http\Resources;

use App\Services\Order\Quote;
use App\Services\Order\QuoteLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Quote */
class QuoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'store_id' => $this->delivery->store->id,
            'delivery_zone_id' => $this->delivery->zone->id,
            'distance_km' => $this->delivery->distanceKm,
            'currency' => $this->delivery->store->currency,
            'items' => array_map(fn (QuoteLine $line) => [
                'product_id' => $line->product->id,
                'name' => $line->product->translate('name'),
                'quantity' => $line->quantity,
                'unit_price' => $line->unitPrice,
                'option_amount' => $line->optionAmount,
                'total' => $line->total(),
            ], $this->lines),
            'subtotal' => $this->subtotal,
            'delivery_fee' => $this->deliveryFee,
            'service_fee' => $this->serviceFee,
            'discount' => $this->discount,
            'total' => $this->total(),
        ];
    }
}
