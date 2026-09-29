<?php

namespace App\Services\Order;

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\StoreProduct;
use App\Services\Delivery\AvailableStore;
use Illuminate\Support\Collection;

class OrderPricingService
{
    /**
     * @param  list<array{product_id: int, quantity: int, option_ids?: list<int>}>  $items
     * @param  bool  $lockStock  lock store_products rows (used while placing an order)
     */
    public function quote(AvailableStore $delivery, array $items, bool $lockStock = false): Quote
    {
        $store = $delivery->store;
        $productIds = array_values(array_unique(array_column($items, 'product_id')));

        $storeProducts = StoreProduct::query()
            ->where('store_id', $store->id)
            ->whereIn('product_id', $productIds)
            ->when($lockStock, fn ($q) => $q->lockForUpdate())
            ->get()
            ->keyBy('product_id');

        /** @var Collection<int, Product> $products */
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->where('organization_id', $store->organization_id)
            ->withTranslations()
            ->with(['optionGroups.options' => fn ($q) => $q->withTranslations()])
            ->get()
            ->keyBy('id');

        $requested = [];
        foreach ($items as $item) {
            $requested[$item['product_id']] = ($requested[$item['product_id']] ?? 0) + $item['quantity'];
        }

        $lines = [];
        foreach (array_values($items) as $index => $item) {
            $product = $products->get($item['product_id']);
            $storeProduct = $storeProducts->get($item['product_id']);

            if (! $product || ! $product->is_active || ! $storeProduct || ! $storeProduct->is_available
                || ($storeProduct->stock_quantity !== null && $storeProduct->stock_quantity < $requested[$product->id])) {
                throw new ApiException(ErrorCode::PRODUCT_NOT_AVAILABLE, [
                    "items.{$index}.product_id" => [ErrorCode::PRODUCT_NOT_AVAILABLE->message()],
                ]);
            }

            $options = $this->resolveOptions($product, $item['option_ids'] ?? [], $index);
            $storeProduct->setRelation('product', $product);

            $lines[] = new QuoteLine(
                product: $product,
                storeProduct: $storeProduct,
                options: $options,
                quantity: $item['quantity'],
                unitPrice: $storeProduct->effectivePrice(),
                optionAmount: array_sum(array_map(fn (ProductOption $o) => $o->price, $options)),
            );
        }

        return new Quote(
            delivery: $delivery,
            lines: $lines,
            subtotal: array_sum(array_map(fn (QuoteLine $l) => $l->total(), $lines)),
            deliveryFee: $delivery->deliveryFee,
            serviceFee: (int) config('bento.pricing.service_fee'),
            discount: 0,
        );
    }

    /**
     * Options must belong to the product, be active, and satisfy each group's min/max.
     *
     * @param  list<int>  $optionIds
     * @return list<ProductOption>
     */
    private function resolveOptions(Product $product, array $optionIds, int $index): array
    {
        $optionIds = array_values(array_unique($optionIds));
        $chosen = [];

        foreach ($product->optionGroups as $group) {
            $inGroup = $group->options->filter(fn (ProductOption $o) => $o->is_active && in_array($o->id, $optionIds, true));
            if ($inGroup->count() < $group->min_select || $inGroup->count() > $group->max_select) {
                $this->invalidOptions($index);
            }
            array_push($chosen, ...$inGroup->values()->all());
        }

        if (count($chosen) !== count($optionIds)) {
            $this->invalidOptions($index);
        }

        return $chosen;
    }

    private function invalidOptions(int $index): never
    {
        throw new ApiException(ErrorCode::VALIDATION_FAILED, [
            "items.{$index}.option_ids" => [__('validation.in', ['attribute' => "items.{$index}.option_ids"])],
        ]);
    }
}
