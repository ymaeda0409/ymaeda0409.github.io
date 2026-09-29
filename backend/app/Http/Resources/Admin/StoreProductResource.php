<?php

namespace App\Http\Resources\Admin;

use App\Models\StoreProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StoreProduct */
class StoreProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'store_id' => $this->store_id,
            'product_id' => $this->product_id,
            'sku' => $this->product->sku,
            'name' => $this->product->translate('name'),
            'base_price' => $this->product->price,
            'price' => $this->price,
            'effective_price' => $this->effectivePrice(),
            'is_available' => $this->is_available,
            'stock_quantity' => $this->stock_quantity,
            'sort_order' => $this->sort_order,
        ];
    }
}
