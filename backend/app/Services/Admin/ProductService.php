<?php

namespace App\Services\Admin;

use App\Models\Product;
use App\Models\ProductOptionGroup;
use App\Services\AuditLogger;
use App\Services\TranslationService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly TranslationService $translations,
    ) {}

    public function create(int $organizationId, array $data): Product
    {
        return DB::transaction(function () use ($organizationId, $data) {
            $product = Product::create([
                ...Arr::except($data, ['translations', 'option_groups']),
                'organization_id' => $organizationId,
            ]);
            $this->translations->sync($product, $data['translations']);
            $this->syncOptionGroups($product, $data['option_groups'] ?? []);
            $this->audit->log('product.created', $product, null, $data);

            return $product;
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $before = ['translations' => $product->translationsByLocale()];
            $product->fill(Arr::except($data, ['translations', 'option_groups']));
            $before += array_intersect_key($product->getOriginal(), $product->getDirty());
            $product->save();

            if (array_key_exists('translations', $data)) {
                $this->translations->sync($product, $data['translations']);
            }

            if (array_key_exists('option_groups', $data)) {
                $this->syncOptionGroups($product, $data['option_groups'] ?? []);
            }

            $this->audit->log('product.updated', $product, $before, $data);

            return $product;
        });
    }

    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product) {
            $this->audit->log('product.deleted', $product, $product->getAttributes());
            $product->delete();
        });
    }

    /**
     * Upserts option groups/options by id; items missing from the payload are removed.
     * Orders keep name/price snapshots, so removing options never alters history.
     */
    private function syncOptionGroups(Product $product, array $groups): void
    {
        $keptGroupIds = [];

        foreach (array_values($groups) as $index => $groupData) {
            $group = isset($groupData['id'])
                ? $product->optionGroups()->findOrFail($groupData['id'])
                : new ProductOptionGroup(['product_id' => $product->id]);

            $group->fill([
                'min_select' => $groupData['min_select'] ?? 0,
                'max_select' => $groupData['max_select'] ?? 1,
                'sort_order' => $groupData['sort_order'] ?? $index,
            ])->save();
            $this->translations->sync($group, $groupData['translations'] ?? []);
            $keptGroupIds[] = $group->id;

            $keptOptionIds = [];
            foreach (array_values($groupData['options'] ?? []) as $optionIndex => $optionData) {
                $option = isset($optionData['id'])
                    ? $group->options()->findOrFail($optionData['id'])
                    : $group->options()->make();

                $option->fill([
                    'price' => $optionData['price'] ?? 0,
                    'is_active' => $optionData['is_active'] ?? true,
                    'sort_order' => $optionData['sort_order'] ?? $optionIndex,
                ])->save();
                $this->translations->sync($option, $optionData['translations'] ?? []);
                $keptOptionIds[] = $option->id;
            }

            $group->options()->whereNotIn('id', $keptOptionIds)->delete();
        }

        $product->optionGroups()->whereNotIn('id', $keptGroupIds)->delete();
    }
}
