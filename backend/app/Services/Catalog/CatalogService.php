<?php

namespace App\Services\Catalog;

use App\Enums\FranchiseStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Customer-facing catalog: organization products filtered and priced per store.
 */
class CatalogService
{
    /**
     * A store visible to customers: active and belonging to an active franchise.
     */
    public function findPublicStore(int $storeId): Store
    {
        return Store::query()
            ->active()
            ->whereHas('franchise', fn (Builder $q) => $q->where('status', FranchiseStatus::ACTIVE))
            ->withTranslations()
            ->findOrFail($storeId);
    }

    /**
     * Active categories that contain at least one product sold at the store.
     *
     * @return Collection<int, ProductCategory>
     */
    public function categoriesForStore(Store $store): Collection
    {
        return ProductCategory::query()
            ->where('organization_id', $store->organization_id)
            ->where('is_active', true)
            ->whereHas('products', fn (Builder $q) => $this->sellableAt($q, $store))
            ->withTranslations()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function productsForStore(Store $store, ?int $categoryId = null, bool $featuredOnly = false): Collection
    {
        return $this->productQuery($store)
            ->when($categoryId, fn (Builder $q) => $q->where('category_id', $categoryId))
            ->when($featuredOnly, fn (Builder $q) => $q->where('is_featured', true))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function productForStore(Store $store, int $productId): Product
    {
        return $this->productQuery($store)
            ->with(['optionGroups' => fn ($q) => $q
                ->withTranslations()
                ->with(['options' => fn ($q) => $q->where('is_active', true)->withTranslations()]),
            ])
            ->findOrFail($productId);
    }

    private function productQuery(Store $store): Builder
    {
        return $this->sellableAt(Product::query(), $store)
            ->withTranslations()
            ->with(['storeProducts' => fn ($q) => $q->where('store_id', $store->id)]);
    }

    /**
     * Products that are active in the catalog and listed as available at the store.
     * Sold-out items (stock 0) stay listed so the app can show them as unavailable.
     */
    private function sellableAt(Builder $query, Store $store): Builder
    {
        return $query
            ->where('organization_id', $store->organization_id)
            ->where('is_active', true)
            ->whereHas('storeProducts', fn (Builder $q) => $q
                ->where('store_id', $store->id)
                ->where('is_available', true));
    }
}
