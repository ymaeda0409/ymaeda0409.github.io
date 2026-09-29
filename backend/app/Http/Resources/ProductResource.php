<?php

namespace App\Http\Resources;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * Customer-facing product priced for one store (expects `storeProducts` loaded for that store).
 *
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    public function __construct($resource, private readonly Store $store)
    {
        parent::__construct($resource);
    }

    /**
     * @param  iterable<Product>  $products
     * @return Collection<int, self>
     */
    public static function forStore(iterable $products, Store $store): Collection
    {
        return collect($products)->map(fn ($product) => new self($product, $store));
    }

    public function toArray(Request $request): array
    {
        $storeProduct = $this->storeProducts->firstWhere('store_id', $this->store->id);

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'category_id' => $this->category_id,
            'name' => $this->translate('name'),
            'description' => $this->translate('description'),
            'image_url' => $this->image_url,
            'price' => $storeProduct?->price ?? $this->price,
            'currency' => $this->store->currency,
            'preparation_minutes' => $this->preparation_minutes,
            'is_featured' => $this->is_featured,
            'stock_quantity' => $storeProduct?->stock_quantity,
            'is_sold_out' => ! ($storeProduct?->isSellable() ?? false),
            'option_groups' => $this->whenLoaded('optionGroups', fn () => $this->optionGroups->map(fn ($group) => [
                'id' => $group->id,
                'name' => $group->translate('name'),
                'min_select' => $group->min_select,
                'max_select' => $group->max_select,
                'options' => $group->options->map(fn ($option) => [
                    'id' => $option->id,
                    'name' => $option->translate('name'),
                    'price' => $option->price,
                ])->all(),
            ])->all()),
        ];
    }
}
