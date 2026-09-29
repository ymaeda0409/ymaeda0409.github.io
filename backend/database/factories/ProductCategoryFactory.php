<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductCategory>
 */
class ProductCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'code' => fake()->unique()->slug(1),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    /**
     * @param  array<string, string>  $names  locale => name
     */
    public function named(array $names): static
    {
        return $this->afterCreating(function (ProductCategory $category) use ($names) {
            foreach ($names as $locale => $name) {
                $category->translations()->create(['locale' => $locale, 'name' => $name]);
            }
        });
    }
}
