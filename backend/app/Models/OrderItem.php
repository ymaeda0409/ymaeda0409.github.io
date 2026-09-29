<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Snapshot of what was ordered: name (in the order's locale) and prices never change afterwards.
 */
#[Fillable([
    'order_id', 'product_id', 'locale', 'product_name_snapshot', 'product_description_snapshot',
    'quantity', 'unit_price', 'option_amount', 'total',
])]
class OrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'option_amount' => 'integer',
            'total' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function options(): HasMany
    {
        return $this->hasMany(OrderItemOption::class);
    }
}
