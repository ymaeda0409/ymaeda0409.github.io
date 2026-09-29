<?php

namespace App\Services\Order;

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\StoreProduct;

final readonly class QuoteLine
{
    /**
     * @param  list<ProductOption>  $options
     */
    public function __construct(
        public Product $product,
        public StoreProduct $storeProduct,
        public array $options,
        public int $quantity,
        public int $unitPrice,
        public int $optionAmount,
    ) {}

    public function total(): int
    {
        return ($this->unitPrice + $this->optionAmount) * $this->quantity;
    }
}
