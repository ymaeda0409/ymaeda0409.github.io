<?php

namespace App\Http\Requests\Order;

trait OrderItemsRules
{
    protected function itemRules(): array
    {
        return [
            'store_id' => ['required', 'integer'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'distinct:strict'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:'.config('bento.pricing.max_item_quantity')],
            'items.*.option_ids' => ['sometimes', 'array', 'max:20'],
            'items.*.option_ids.*' => ['integer'],
        ];
    }
}
