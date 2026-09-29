<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => ProductCategory::factory(),
            'organization_id' => fn (array $a) => ProductCategory::find($a['category_id'])->organization_id,
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'price' => 350000,
            'preparation_minutes' => 15,
            'is_featured' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * @param  array<string, array{name: string, description?: string}>  $translations
     */
    public function translated(array $translations): static
    {
        return $this->afterCreating(function (Product $product) use ($translations) {
            foreach ($translations as $locale => $values) {
                $product->translations()->create(['locale' => $locale] + $values);
            }
        });
    }

    public function soldAt(Store $store, array $overrides = []): static
    {
        return $this->afterCreating(function (Product $product) use ($store, $overrides) {
            $product->storeProducts()->create($overrides + ['store_id' => $store->id, 'is_available' => true]);
        });
    }
}
