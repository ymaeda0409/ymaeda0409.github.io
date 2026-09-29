<?php

namespace App\Services\Admin;

use App\Models\Product;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class StoreProductService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function upsert(Store $store, Product $product, array $data): StoreProduct
    {
        return DB::transaction(function () use ($store, $product, $data) {
            $storeProduct = StoreProduct::firstOrNew(['store_id' => $store->id, 'product_id' => $product->id]);
            $storeProduct->fill($data);
            $before = $storeProduct->exists ? array_intersect_key($storeProduct->getOriginal(), $storeProduct->getDirty()) : null;
            $storeProduct->save();

            $this->audit->log('store_product.updated', $store, $before, ['product_id' => $product->id] + $data);

            return $storeProduct->setRelation('product', $product);
        });
    }
}
