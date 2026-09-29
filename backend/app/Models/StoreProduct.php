<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Store-level sellability, price override and stock for an organization-level product.
 * Tenant access is authorized through the owning Store.
 */
#[Fillable(['store_id', 'product_id', 'is_available', 'price', 'stock_quantity', 'sort_order'])]
class StoreProduct extends Model
{
    /**
     * Mirrors the column defaults so freshly created models serialize completely.
     */
    protected $attributes = ['is_available' => true, 'sort_order' => 0];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'price' => 'integer',
            'stock_quantity' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function effectivePrice(): int
    {
        return $this->price ?? $this->product->price;
    }

    public function isSellable(): bool
    {
        return $this->is_available && ($this->stock_quantity === null || $this->stock_quantity > 0);
    }
}
